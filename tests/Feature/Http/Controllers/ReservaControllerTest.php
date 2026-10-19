<?php

declare(strict_types=1);

use App\Contracts\DisponibilidadService;
use App\Enums\ReservaEstado;
use App\Models\Convenio;
use App\Models\Reserva;
use App\Models\Tarifa;
use App\Models\Trm;
use App\Models\User;
use App\Models\Vehiculo;
use DateTimeInterface;
use Illuminate\Support\Carbon;

describe('creación de reservas', function () {
    it('persists a pending reservation for the authenticated client with a server-calculated quote', function () {
        $cliente = User::factory()->cliente()->create();
        $otroCliente = User::factory()->cliente()->create();
        $vehiculo = Vehiculo::factory()->create();
        Tarifa::factory()->create([
            'vehiculo_id' => $vehiculo->id,
            'tipo_vehiculo' => $vehiculo->tipo,
            'valor_diario_centavos' => 15000000,
            'vigente_desde' => '2026-01-01',
        ]);

        $respuesta = $this->actingAs($cliente)->post(route('reservas.store'), [
            'user_id' => $otroCliente->id,
            'vehiculo_id' => $vehiculo->id,
            'inicio' => '2026-11-10 09:00:00',
            'fin' => '2026-11-12 09:00:00',
        ]);

        $reserva = Reserva::query()->sole();

        $respuesta->assertRedirectToRoute('reservas.show', $reserva);

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'user_id' => $cliente->id,
            'vehiculo_id' => $vehiculo->id,
            'estado' => 'pendiente',
            'inicio' => '2026-11-10 09:00:00',
            'fin' => '2026-11-12 09:00:00',
            'total_centavos' => 35700000,
            'moneda' => 'COP',
            'qr_codigo' => null,
        ]);
    });

    it('applies the agreement discount when creating a reservation', function () {
        $cliente = User::factory()->cliente()->create();
        $vehiculo = Vehiculo::factory()->create();
        Tarifa::factory()->create([
            'vehiculo_id' => $vehiculo->id,
            'tipo_vehiculo' => $vehiculo->tipo,
            'valor_diario_centavos' => 15000000,
            'vigente_desde' => '2026-01-01',
        ]);
        $convenio = Convenio::factory()->create(['descuento_porcentaje' => 10]);

        $respuesta = $this->actingAs($cliente)->post(route('reservas.store'), [
            'vehiculo_id' => $vehiculo->id,
            'convenio_id' => $convenio->id,
            'inicio' => '2026-11-10 09:00:00',
            'fin' => '2026-11-12 09:00:00',
        ]);

        $reserva = Reserva::query()->sole();

        $respuesta->assertRedirectToRoute('reservas.show', $reserva);

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'convenio_id' => $convenio->id,
            'total_centavos' => 32700000,
            'estado' => 'pendiente',
        ]);
    });

    it('persists a reservation quote in USD using the current saved TRM', function () {
        $this->travelTo(Carbon::parse('2026-11-01 10:00:00'));
        $cliente = User::factory()->cliente()->create();
        $vehiculo = Vehiculo::factory()->create();
        Tarifa::factory()->create([
            'vehiculo_id' => $vehiculo->id,
            'tipo_vehiculo' => $vehiculo->tipo,
            'valor_diario_centavos' => 15000000,
            'vigente_desde' => '2026-01-01',
        ]);
        Trm::factory()->create([
            'fecha' => '2026-11-01',
            'valor' => '4200.2500',
        ]);

        $respuesta = $this->actingAs($cliente)->post(route('reservas.store'), [
            'vehiculo_id' => $vehiculo->id,
            'inicio' => '2026-11-10 09:00:00',
            'fin' => '2026-11-12 09:00:00',
            'moneda' => 'USD',
        ]);

        $reserva = Reserva::query()->sole();

        $respuesta->assertRedirectToRoute('reservas.show', $reserva);

        expect($reserva->total_centavos)->toBe(8499)
            ->and($reserva->moneda)->toBe('USD');
    });

    it('exposes only rental notes backed by the MVP instead of deposit, age or free cancellation', function () {
        $cliente = User::factory()->cliente()->create();

        $this->actingAs($cliente)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get(route('reservas.create'))
            ->assertOk()
            ->assertJsonPath('props.rentalNotes.0', 'La licencia de conducción vigente se verifica al entregar el vehículo.')
            ->assertJsonPath('props.rentalNotes.1', 'Combustible: devolver al mismo nivel; hay cargo si falta.')
            ->assertJsonPath('props.rentalNotes.2', 'Entrega y devolución en la sede elegida (informativo).')
            ->assertJsonPath(
                'props.rentalDisclaimer',
                'Sin cargos al reservar. El cobro se hace al devolver el vehículo. Cancelar una reserva no forma parte de este MVP.',
            );

        $notes = $this->actingAs($cliente)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get(route('reservas.create'))
            ->json('props.rentalNotes');

        expect($notes)->not->toContain('Licencia tipo B requerida')
            ->and($notes)->not->toContain('Edad mínima: 21 años')
            ->and($notes)->not->toContain('Depósito: $500 (reembolsable)');
    });

    it('shows the current TRM on the reservation form and ignores a future rate', function () {
        $this->travelTo(Carbon::parse('2026-11-01 10:00:00'));
        $cliente = User::factory()->cliente()->create();
        Trm::factory()->create([
            'fecha' => '2026-10-31',
            'valor' => '4200.0000',
        ]);
        Trm::factory()->create([
            'fecha' => '2026-11-02',
            'valor' => '5000.0000',
        ]);

        $this->actingAs($cliente)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get(route('reservas.create'))
            ->assertOk()
            ->assertJsonPath('props.trm', '4200.0000');
    });

    it('rejects an unsupported reservation currency before creating a reservation', function () {
        $cliente = User::factory()->cliente()->create();
        $vehiculo = Vehiculo::factory()->create();

        $this->actingAs($cliente)->post(route('reservas.store'), [
            'vehiculo_id' => $vehiculo->id,
            'inicio' => '2026-11-10 09:00:00',
            'fin' => '2026-11-12 09:00:00',
            'moneda' => 'EUR',
        ])->assertSessionHasErrors('moneda');

        expect(Reserva::query()->count())->toBe(0);
    });

    it('redirects guests to sign in without creating a reservation', function () {
        $vehiculo = Vehiculo::factory()->create();

        $this->post(route('reservas.store'), [
            'vehiculo_id' => $vehiculo->id,
            'inicio' => '2026-11-10 09:00:00',
            'fin' => '2026-11-12 09:00:00',
        ])->assertRedirectToRoute('login');

        expect(Reserva::query()->count())->toBe(0);
    });

    it('rejects invalid reservation dates without persisting a reservation', function () {
        $cliente = User::factory()->cliente()->create();
        $vehiculo = Vehiculo::factory()->create();

        $this->actingAs($cliente)->post(route('reservas.store'), [
            'vehiculo_id' => $vehiculo->id,
            'inicio' => '2026-11-12 09:00:00',
            'fin' => '2026-11-10 09:00:00',
        ])->assertSessionHasErrors([
            'fin' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ]);

        expect(Reserva::query()->count())->toBe(0);
    });

    it('rejects a vehicle that does not exist', function () {
        $cliente = User::factory()->cliente()->create();

        $this->actingAs($cliente)->post(route('reservas.store'), [
            'vehiculo_id' => 999999,
            'inicio' => '2026-11-10 09:00:00',
            'fin' => '2026-11-12 09:00:00',
        ])->assertSessionHasErrors([
            'vehiculo_id' => 'El vehículo seleccionado no existe.',
        ]);

        expect(Reserva::query()->count())->toBe(0);
    });

    it('forbids non-client roles from creating reservations', function () {
        $coordinador = User::factory()->coordinador()->create();
        $vehiculo = Vehiculo::factory()->create();

        $this->actingAs($coordinador)->post(route('reservas.store'), [
            'vehiculo_id' => $vehiculo->id,
            'inicio' => '2026-11-10 09:00:00',
            'fin' => '2026-11-12 09:00:00',
        ])->assertForbidden();

        expect(Reserva::query()->count())->toBe(0);
    });

    it('rejects a reservation when the availability contract reports an occupied vehicle', function () {
        $cliente = User::factory()->cliente()->create();
        $vehiculo = Vehiculo::factory()->create();

        app()->instance(DisponibilidadService::class, new class implements DisponibilidadService
        {
            public function consultar(
                DateTimeInterface $inicio,
                DateTimeInterface $fin,
                ?int $vehiculoId = null,
            ): array {
                return [[
                    'vehiculo_id' => $vehiculoId ?? 0,
                    'disponible' => false,
                    'motivo' => 'El vehículo tiene una reserva para esas fechas.',
                ]];
            }
        });

        $this->actingAs($cliente)->post(route('reservas.store'), [
            'vehiculo_id' => $vehiculo->id,
            'inicio' => '2026-11-10 09:00:00',
            'fin' => '2026-11-12 09:00:00',
        ])->assertSessionHasErrors([
            'vehiculo_id' => 'El vehículo tiene una reserva para esas fechas.',
        ]);

        expect(Reserva::query()->count())->toBe(0);
    });
});

describe('confirmación y búsqueda de reservas', function () {
    it('confirms a pending reservation and assigns its unique QR code', function () {
        $cliente = User::factory()->cliente()->create();
        $reserva = Reserva::factory()->pendiente()->create([
            'user_id' => $cliente->id,
        ]);

        $this->actingAs($cliente)
            ->post(route('reservas.confirmar', $reserva))
            ->assertRedirectToRoute('reservas.show', $reserva);

        expect($reserva->fresh()->estado)->toBe(ReservaEstado::Confirmada)
            ->and($reserva->fresh()->qr_codigo)->toBe('FAKE-QR-'.$reserva->id);
    });

    it('assigns a distinct QR code to every confirmed reservation', function () {
        $cliente = User::factory()->cliente()->create();
        $primeraReserva = Reserva::factory()->pendiente()->create(['user_id' => $cliente->id]);
        $segundaReserva = Reserva::factory()->pendiente()->create(['user_id' => $cliente->id]);

        $this->actingAs($cliente)
            ->post(route('reservas.confirmar', $primeraReserva))
            ->assertRedirectToRoute('reservas.show', $primeraReserva);
        $this->actingAs($cliente)
            ->post(route('reservas.confirmar', $segundaReserva))
            ->assertRedirectToRoute('reservas.show', $segundaReserva);

        expect($primeraReserva->fresh()->qr_codigo)->not->toBe($segundaReserva->fresh()->qr_codigo)
            ->and(Reserva::query()->whereNotNull('qr_codigo')->count())->toBe(2);
    });

    it('does not confirm another client reservation', function () {
        $cliente = User::factory()->cliente()->create();
        $otroCliente = User::factory()->cliente()->create();
        $reserva = Reserva::factory()->pendiente()->create(['user_id' => $otroCliente->id]);

        $this->actingAs($cliente)
            ->post(route('reservas.confirmar', $reserva))
            ->assertForbidden();

        expect($reserva->fresh()->estado)->toBe(ReservaEstado::Pendiente)
            ->and($reserva->fresh()->qr_codigo)->toBeNull();
    });

    it('redirects guests to sign in when confirming a reservation', function () {
        $reserva = Reserva::factory()->pendiente()->create();

        $this->post(route('reservas.confirmar', $reserva))
            ->assertRedirectToRoute('login');

        expect($reserva->fresh()->estado)->toBe(ReservaEstado::Pendiente)
            ->and($reserva->fresh()->qr_codigo)->toBeNull();
    });

    it('locates the reservation from its QR code', function () {
        $reserva = Reserva::factory()->create([
            'estado' => ReservaEstado::Confirmada,
            'qr_codigo' => 'FAKE-QR-VERIFICABLE',
        ]);

        $this->get(route('reservas.qr', 'FAKE-QR-VERIFICABLE'))
            ->assertRedirectToRoute('reservas.show', $reserva);
    });

    it('returns not found for an unknown QR code', function () {
        $this->get(route('reservas.qr', 'QR-INEXISTENTE'))
            ->assertNotFound();
    });

    it('does not confirm a reservation more than once', function () {
        $cliente = User::factory()->cliente()->create();
        $reserva = Reserva::factory()->create([
            'user_id' => $cliente->id,
            'estado' => ReservaEstado::Confirmada,
            'qr_codigo' => 'FAKE-QR-EXISTENTE',
        ]);

        $this->actingAs($cliente)
            ->post(route('reservas.confirmar', $reserva))
            ->assertSessionHasErrors([
                'reserva' => 'Solo se pueden confirmar reservas pendientes.',
            ]);

        expect($reserva->fresh()->estado)->toBe(ReservaEstado::Confirmada)
            ->and($reserva->fresh()->qr_codigo)->toBe('FAKE-QR-EXISTENTE');
    });

    it('keeps the chosen calendar dates in America/Bogota on the reservation receipt', function () {
        expect(config('app.timezone'))->toBe('America/Bogota');

        $cliente = User::factory()->cliente()->create();
        $vehiculo = Vehiculo::factory()->create();
        Tarifa::factory()->create([
            'vehiculo_id' => $vehiculo->id,
            'tipo_vehiculo' => $vehiculo->tipo,
            'valor_diario_centavos' => 15000000,
            'vigente_desde' => '2026-01-01',
        ]);

        $this->actingAs($cliente)->post(route('reservas.store'), [
            'vehiculo_id' => $vehiculo->id,
            'inicio' => '2026-10-19',
            'fin' => '2026-10-21',
        ])->assertRedirect();

        $reserva = Reserva::query()->sole();

        $this->actingAs($cliente)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get(route('reservas.show', $reserva))
            ->assertOk()
            ->assertJsonPath('props.reservation.startsAt', '2026-10-19T00:00:00-05:00')
            ->assertJsonPath('props.reservation.endsAt', '2026-10-21T00:00:00-05:00');

        expect($reserva->inicio->timezone('America/Bogota')->format('d/m/Y'))->toBe('19/10/2026')
            ->and($reserva->fin->timezone('America/Bogota')->format('d/m/Y'))->toBe('21/10/2026')
            ->and($reserva->total_centavos)->toBe(35700000)
            ->and(app(DisponibilidadService::class)->consultar(
                Carbon::parse('2026-10-19'),
                Carbon::parse('2026-10-21'),
                $vehiculo->id,
            )[0]['disponible'])->toBeFalse();
    });
});
