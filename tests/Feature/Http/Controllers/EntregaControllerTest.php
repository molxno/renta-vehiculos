<?php

declare(strict_types=1);

use App\Enums\ReservaEstado;
use App\Enums\UsuarioRol;
use App\Models\ActaEntrega;
use App\Models\LicenciaConduccion;
use App\Models\Reserva;
use App\Models\User;
use Database\Seeders\ReservaConfirmadaSeeder;

function cabecerasInertiaEntrega(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ];
}

describe('listado de entregas', function () {
    it('redirects guests from the delivery index to login', function () {
        $this->get(route('entregas.index'))->assertRedirectToRoute('login');
    });

    it('forbids a client from listing deliveries', function () {
        $this->actingAs(User::factory()->cliente()->create())
            ->get(route('entregas.index'))
            ->assertForbidden();
    });

    it('lists confirmed reservations for a coordinator', function () {
        $this->seed(ReservaConfirmadaSeeder::class);
        $confirmada = Reserva::query()->where('qr_codigo', 'QR-RESERVA-CONFIRMADA')->sole();
        Reserva::factory()->pendiente()->create();

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaEntrega())
            ->get(route('entregas.index'))
            ->assertOk()
            ->assertJsonPath('component', 'Entregas/Index')
            ->assertJsonPath('props.reservations.2.id', $confirmada->id)
            ->assertJsonPath('props.reservations.2.qrCode', 'QR-RESERVA-CONFIRMADA')
            ->assertJsonPath('props.reservations.2.clientDocument', '1000000001')
            ->assertJsonPath('props.reservations.2.deliverable', true)
            ->assertJsonPath('props.filters.q', '');
    });

    it('disables delivery for future and expired confirmed reservations', function () {
        $this->seed(ReservaConfirmadaSeeder::class);

        autenticarEmpleado();

        $payload = $this->withHeaders(cabecerasInertiaEntrega())
            ->get(route('entregas.index'))
            ->assertOk()
            ->json('props.reservations');

        $porCodigo = collect($payload)->keyBy('qrCode');

        expect($porCodigo['QR-RESERVA-CONFIRMADA']['deliverable'])->toBeTrue()
            ->and($porCodigo['QR-RESERVA-CONFIRMADA']['deliveryReason'])->toBeNull()
            ->and($porCodigo['QR-RESERVA-FUTURA']['deliverable'])->toBeFalse()
            ->and($porCodigo['QR-RESERVA-FUTURA']['deliveryReason'])->toStartWith('Disponible desde ')
            ->and($porCodigo['QR-RESERVA-VENCIDA']['deliverable'])->toBeFalse()
            ->and($porCodigo['QR-RESERVA-VENCIDA']['deliveryReason'])->toBe('Vencida');
    });

    it('allows an administrator to open the delivery index', function () {
        autenticarEmpleado(UsuarioRol::Administrador);

        $this->withHeaders(cabecerasInertiaEntrega())
            ->get(route('entregas.index'))
            ->assertOk()
            ->assertJsonPath('component', 'Entregas/Index');
    });

    it('finds a confirmed reservation by id, fake QR or client document', function (string $campo) {
        $this->seed(ReservaConfirmadaSeeder::class);
        $confirmada = Reserva::query()->where('qr_codigo', 'QR-RESERVA-CONFIRMADA')->sole();
        Reserva::factory()->create([
            'estado' => ReservaEstado::Confirmada,
            'qr_codigo' => 'QR-OTRA-CONFIRMADA',
        ]);

        $termino = match ($campo) {
            'id' => (string) $confirmada->id,
            'qr' => 'QR-RESERVA-CONFIRMADA',
            'fake-qr' => 'FAKE-QR-'.$confirmada->id,
            default => '1000000001',
        };

        autenticarEmpleado();

        $respuesta = $this->withHeaders(cabecerasInertiaEntrega())
            ->get(route('entregas.index', ['q' => $termino]))
            ->assertOk()
            ->assertJsonPath('props.filters.q', $termino);

        $ids = collect($respuesta->json('props.reservations'))->pluck('id');

        expect($ids)->toContain($confirmada->id);

        if ($campo === 'documento') {
            $respuesta->assertJsonCount(3, 'props.reservations');
        } else {
            $respuesta->assertJsonCount(1, 'props.reservations')
                ->assertJsonPath('props.reservations.0.id', $confirmada->id);
        }
    })->with(['id', 'qr', 'fake-qr', 'documento']);
});

describe('formulario y éxito de entrega', function () {
    it('shows reservation, client, vehicle and license on the delivery form', function () {
        $reservation = Reserva::factory()->enVentanaDeEntrega()->create([
            'estado' => ReservaEstado::Confirmada,
            'qr_codigo' => 'FAKE-QR-DEMO',
        ]);
        LicenciaConduccion::factory()->create([
            'user_id' => $reservation->user_id,
            'numero' => 'LIC-VIGENTE',
            'fecha_vencimiento' => '2028-12-31',
            'categoria' => 'B1',
        ]);

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaEntrega())
            ->get(route('entregas.create', $reservation))
            ->assertOk()
            ->assertJsonPath('component', 'Entregas/Create')
            ->assertJsonPath('props.cliente.nombre', $reservation->user->name)
            ->assertJsonPath('props.cliente.documento', $reservation->user->numero_documento)
            ->assertJsonPath('props.vehiculo.placa', $reservation->vehiculo->placa)
            ->assertJsonPath('props.qrCodigo', 'FAKE-QR-DEMO')
            ->assertJsonPath('props.licencia.categoria_valida', true)
            ->assertJsonPath('props.bloqueo', null);
    });

    it('blocks the delivery form when the license category is invalid', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Confirmada]);
        LicenciaConduccion::factory()->create([
            'user_id' => $reservation->user_id,
            'fecha_vencimiento' => '2028-12-31',
            'categoria' => 'XYZ',
        ]);

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaEntrega())
            ->get(route('entregas.create', $reservation))
            ->assertOk()
            ->assertJsonPath('props.licencia.categoria_valida', false)
            ->assertJsonPath('props.bloqueo', 'La categoría de la licencia no es válida para entregar el vehículo.');
    });

    it('rejects storing a delivery when the license category is invalid', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Confirmada]);
        LicenciaConduccion::factory()->create([
            'user_id' => $reservation->user_id,
            'fecha_vencimiento' => '2028-12-31',
            'categoria' => 'XYZ',
        ]);

        autenticarEmpleado();

        $this->from(route('entregas.create', $reservation))
            ->post(route('entregas.store', $reservation), [
                'entregado_at' => '2026-02-01 09:00:00',
                'aceptacion' => '1',
            ])
            ->assertRedirectToRoute('entregas.create', $reservation)
            ->assertSessionHasErrors([
                'licencia' => 'La categoría de la licencia no es válida para entregar el vehículo.',
            ]);

        expect($reservation->fresh()->estado)->toBe(ReservaEstado::Confirmada);
    });

    it('redirects a successful delivery to the success page', function () {
        $reservation = Reserva::factory()->enVentanaDeEntrega()->create(['estado' => ReservaEstado::Confirmada]);
        LicenciaConduccion::factory()->create([
            'user_id' => $reservation->user_id,
            'fecha_vencimiento' => '2028-12-31',
            'categoria' => 'B1',
        ]);

        autenticarEmpleado();

        $this->post(route('entregas.store', $reservation), [
            'entregado_at' => '2026-02-01 09:00:00',
            'checklist' => [
                'kilometraje' => '12500',
                'combustible' => 'lleno',
            ],
            'aceptacion' => '1',
        ])->assertRedirectToRoute('entregas.show', $reservation);

        expect($reservation->fresh()->estado)->toBe(ReservaEstado::Entregada);
    });

    it('shows the delivery summary and a link to register the return', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);
        ActaEntrega::factory()->create([
            'reserva_id' => $reservation->id,
            'numero' => 'ACT-SHOW',
            'entregado_at' => '2026-02-01 09:00:00',
            'checklist' => ['kilometraje' => 12500, 'combustible' => 'lleno'],
        ]);

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaEntrega())
            ->get(route('entregas.show', $reservation))
            ->assertOk()
            ->assertJsonPath('component', 'Entregas/Show')
            ->assertJsonPath('props.reservation.id', $reservation->id)
            ->assertJsonPath('props.reservation.clientName', $reservation->user->name)
            ->assertJsonPath('props.acta.kilometraje', 12500)
            ->assertJsonPath('props.acta.combustible', 'lleno')
            ->assertJsonPath('props.acta.numero', 'ACT-SHOW')
            ->assertJsonPath('props.acta.actaUrl', route('entregas.acta', $reservation))
            ->assertJsonPath('props.devolucionUrl', route('devoluciones.create', $reservation));
    });

    it('redirects the delivery form to the listing when the reservation is outside the window', function () {
        $reservation = Reserva::factory()->create([
            'estado' => ReservaEstado::Confirmada,
            'inicio' => now()->addDays(2)->startOfDay(),
            'fin' => now()->addDays(5)->startOfDay(),
        ]);
        LicenciaConduccion::factory()->create([
            'user_id' => $reservation->user_id,
            'fecha_vencimiento' => now()->addYear()->toDateString(),
            'categoria' => 'B1',
        ]);

        autenticarEmpleado();

        $this->from(route('entregas.index'))
            ->get(route('entregas.create', $reservation))
            ->assertRedirectToRoute('entregas.index')
            ->assertSessionHas('error');
    });

    it('rejects storing a delivery outside the window with a flash error', function () {
        $reservation = Reserva::factory()->create([
            'estado' => ReservaEstado::Confirmada,
            'inicio' => now()->addDays(2)->startOfDay(),
            'fin' => now()->addDays(5)->startOfDay(),
        ]);
        LicenciaConduccion::factory()->create([
            'user_id' => $reservation->user_id,
            'fecha_vencimiento' => now()->addYear()->toDateString(),
            'categoria' => 'B1',
        ]);

        autenticarEmpleado();

        $this->from(route('entregas.create', $reservation))
            ->post(route('entregas.store', $reservation), [
                'entregado_at' => now()->toDateTimeString(),
                'aceptacion' => '1',
            ])
            ->assertRedirectToRoute('entregas.index')
            ->assertSessionHas('error');

        expect($reservation->fresh()->estado)->toBe(ReservaEstado::Confirmada);
    });

    it('redirects the success page to the form when the reservation is not delivered', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Confirmada]);

        autenticarEmpleado();

        $this->get(route('entregas.show', $reservation))
            ->assertRedirectToRoute('entregas.create', $reservation);
    });

    it('shows empty acta details when a delivered reservation has no checklist values', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaEntrega())
            ->get(route('entregas.show', $reservation))
            ->assertOk()
            ->assertJsonPath('props.acta.entregadoAt', null)
            ->assertJsonPath('props.acta.kilometraje', null)
            ->assertJsonPath('props.acta.combustible', null)
            ->assertJsonPath('props.acta.numero', null)
            ->assertJsonPath('props.acta.actaUrl', null);
    });

    it('falls back to ACT-id on the success page when the stored number is empty', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);
        $acta = ActaEntrega::factory()->create([
            'reserva_id' => $reservation->id,
            'numero' => '',
            'checklist' => null,
        ]);

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaEntrega())
            ->get(route('entregas.show', $reservation))
            ->assertOk()
            ->assertJsonPath('props.acta.numero', 'ACT-'.$acta->id)
            ->assertJsonPath('props.acta.actaUrl', route('entregas.acta', $reservation));
    });

    it('forbids a client from opening the delivery success page', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);

        $this->actingAs(User::factory()->cliente()->create())
            ->get(route('entregas.show', $reservation))
            ->assertForbidden();
    });

    it('forbids a cashier from opening delivery pages', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Confirmada]);

        autenticarEmpleado(UsuarioRol::Cajero);

        $this->get(route('entregas.index'))->assertForbidden();
        $this->get(route('entregas.create', $reservation))->assertForbidden();
        $this->get(route('entregas.show', $reservation))->assertForbidden();
        $this->post(route('entregas.store', $reservation), [])->assertForbidden();
    });
});
