<?php

declare(strict_types=1);

use App\Contracts\DisponibilidadService;
use App\Enums\ReservaEstado;
use App\Models\Convenio;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Vehiculo;
use DateTimeInterface;

describe('creación de reservas', function () {
    it('persists a pending reservation for the authenticated client with a server-calculated quote', function () {
        $cliente = User::factory()->cliente()->create();
        $otroCliente = User::factory()->cliente()->create();
        $vehiculo = Vehiculo::factory()->create();

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
            'total_centavos' => 53550000,
            'moneda' => 'COP',
            'qr_codigo' => null,
        ]);
    });

    it('applies the agreement discount when creating a reservation', function () {
        $cliente = User::factory()->cliente()->create();
        $vehiculo = Vehiculo::factory()->create();
        $convenio = Convenio::factory()->create();

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
            'total_centavos' => 49050000,
            'estado' => 'pendiente',
        ]);
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
});
