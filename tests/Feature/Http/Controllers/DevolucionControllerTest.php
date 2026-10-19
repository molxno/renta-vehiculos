<?php

declare(strict_types=1);

use App\Enums\FacturaEstado;
use App\Enums\ReservaEstado;
use App\Enums\UsuarioRol;
use App\Models\ActaDevolucion;
use App\Models\ActaEntrega;
use App\Models\Factura;
use App\Models\Reserva;
use App\Models\User;
use Database\Seeders\AlquilerEntregadoSeeder;

function cabecerasInertiaDevolucion(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ];
}

describe('listado de devoluciones', function () {
    it('redirects guests from the return index to login', function () {
        $this->get(route('devoluciones.index'))->assertRedirectToRoute('login');
    });

    it('forbids a client from listing returns', function () {
        $this->actingAs(User::factory()->cliente()->create())
            ->get(route('devoluciones.index'))
            ->assertForbidden();
    });

    it('lists delivered reservations for a coordinator', function () {
        $this->seed(AlquilerEntregadoSeeder::class);
        $entregada = Reserva::query()->where('qr_codigo', 'QR-ALQUILER-ENTREGADO')->sole();
        Reserva::factory()->create(['estado' => ReservaEstado::Confirmada]);

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaDevolucion())
            ->get(route('devoluciones.index'))
            ->assertOk()
            ->assertJsonPath('component', 'Devoluciones/Index')
            ->assertJsonPath('props.reservations.0.id', $entregada->id)
            ->assertJsonPath('props.reservations.0.qrCode', 'QR-ALQUILER-ENTREGADO')
            ->assertJsonPath('props.reservations.0.clientDocument', '1000000001')
            ->assertJsonPath('props.filters.q', '');
    });

    it('allows an administrator to open the return index', function () {
        autenticarEmpleado(UsuarioRol::Administrador);

        $this->withHeaders(cabecerasInertiaDevolucion())
            ->get(route('devoluciones.index'))
            ->assertOk()
            ->assertJsonPath('component', 'Devoluciones/Index');
    });

    it('finds a delivered reservation by id, fake QR or client document', function (string $campo) {
        $this->seed(AlquilerEntregadoSeeder::class);
        $entregada = Reserva::query()->where('qr_codigo', 'QR-ALQUILER-ENTREGADO')->sole();
        Reserva::factory()->create([
            'estado' => ReservaEstado::Entregada,
            'qr_codigo' => 'QR-OTRA-ENTREGADA',
        ]);

        $termino = match ($campo) {
            'id' => (string) $entregada->id,
            'qr' => 'QR-ALQUILER-ENTREGADO',
            'fake-qr' => 'FAKE-QR-'.$entregada->id,
            default => '1000000001',
        };

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaDevolucion())
            ->get(route('devoluciones.index', ['q' => $termino]))
            ->assertOk()
            ->assertJsonPath('props.filters.q', $termino)
            ->assertJsonPath('props.reservations.0.id', $entregada->id)
            ->assertJsonCount(1, 'props.reservations');
    })->with(['id', 'qr', 'fake-qr', 'documento']);
});

describe('formulario, vista previa y éxito de devolución', function () {
    it('shows reservation, client and vehicle on the return form', function () {
        $reservation = Reserva::factory()->create([
            'estado' => ReservaEstado::Entregada,
            'qr_codigo' => 'FAKE-QR-DEVOLUCION',
        ]);
        ActaEntrega::factory()->create([
            'reserva_id' => $reservation->id,
            'checklist' => ['kilometraje' => 10000, 'combustible' => 'lleno'],
        ]);

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaDevolucion())
            ->get(route('devoluciones.create', $reservation))
            ->assertOk()
            ->assertJsonPath('component', 'Devoluciones/Create')
            ->assertJsonPath('props.cliente.nombre', $reservation->user->name)
            ->assertJsonPath('props.cliente.documento', $reservation->user->numero_documento)
            ->assertJsonPath('props.vehiculo.placa', $reservation->vehiculo->placa)
            ->assertJsonPath('props.qrCodigo', 'FAKE-QR-DEVOLUCION');
    });

    it('previews delay, mileage, fuel and damage lines before confirming', function () {
        $this->seed(AlquilerEntregadoSeeder::class);
        $reservation = Reserva::query()->where('qr_codigo', 'QR-ALQUILER-ENTREGADO')->sole();

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaDevolucion())
            ->get(route('devoluciones.create', [
                'reserva' => $reservation,
                'devuelto_at' => '2026-02-09 10:00:00',
                'kilometraje' => '10450',
                'combustible' => 'cuarto',
                'danos' => [
                    ['descripcion' => 'Rayón en puerta', 'monto_centavos' => 5000000],
                    'x',
                    ['descripcion' => '', 'monto_centavos' => ''],
                ],
            ]))
            ->assertOk()
            ->assertJsonPath('props.cobro.desglose.1.concepto', 'Retraso de 2 día(s)')
            ->assertJsonPath('props.cobro.desglose.2.concepto', 'Exceso de kilometraje (150 km)')
            ->assertJsonPath('props.cobro.desglose.3.concepto', 'Combustible faltante (3 cuarto(s) de tanque)')
            ->assertJsonPath('props.cobro.desglose.4.concepto', 'Daño: Rayón en puerta')
            ->assertJsonPath('props.cobro.desglose.4.monto_centavos', 5000000)
            ->assertJsonPath('props.filters.danos.0.descripcion', 'Rayón en puerta')
            ->assertJsonPath('props.cobro.subtotal_centavos', 170000000)
            ->assertJsonPath('props.cobro.impuestos_centavos', 32300000)
            ->assertJsonPath('props.cobro.total_centavos', 202300000);
    });

    it('ignores a non array damage payload in the cost preview', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaDevolucion())
            ->get(route('devoluciones.create', $reservation).'?danos=invalido')
            ->assertOk()
            ->assertJsonPath('props.filters.danos', [])
            ->assertJsonPath('props.estimatedChargesCents', 0);
    });

    it('treats a non numeric preview damage amount as zero', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaDevolucion())
            ->get(route('devoluciones.create', [
                'reserva' => $reservation,
                'danos' => [['descripcion' => 'Golpe', 'monto_centavos' => 'abc']],
            ]))
            ->assertOk()
            ->assertJsonPath('props.cobro.desglose.1.concepto', 'Daño: Golpe')
            ->assertJsonPath('props.cobro.desglose.1.monto_centavos', 0);
    });

    it('redirects a successful return to the success page', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);

        autenticarEmpleado();

        $this->post(route('devoluciones.store', $reservation), [
            'devuelto_at' => '2026-02-09 10:00:00',
            'checklist' => [
                'kilometraje' => '10100',
                'combustible' => 'lleno',
            ],
        ])->assertRedirectToRoute('devoluciones.show', $reservation);

        expect($reservation->fresh()->estado)->toBe(ReservaEstado::Facturada);
    });

    it('shows the return summary, invoice breakdown and a link to collect payment', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Facturada]);
        ActaDevolucion::factory()->create([
            'reserva_id' => $reservation->id,
            'devuelto_at' => '2026-02-09 10:00:00',
            'checklist' => ['kilometraje' => 10450, 'combustible' => 'cuarto'],
            'observaciones' => 'Inspección con novedades',
        ]);
        Factura::factory()->create([
            'reserva_id' => $reservation->id,
            'numero' => sprintf('FAC-%06d', $reservation->id),
            'estado' => FacturaEstado::Pendiente,
            'subtotal_centavos' => 170000000,
            'impuestos_centavos' => 32300000,
            'total_centavos' => 202300000,
            'desglose' => [
                ['concepto' => 'Renta base', 'monto_centavos' => 66000000],
                ['concepto' => 'Daño: Rayón en puerta', 'monto_centavos' => 5000000],
            ],
        ]);

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaDevolucion())
            ->get(route('devoluciones.show', $reservation))
            ->assertOk()
            ->assertJsonPath('component', 'Devoluciones/Show')
            ->assertJsonPath('props.reservation.id', $reservation->id)
            ->assertJsonPath('props.reservation.clientName', $reservation->user->name)
            ->assertJsonPath('props.acta.kilometraje', 10450)
            ->assertJsonPath('props.acta.combustible', 'cuarto')
            ->assertJsonPath('props.acta.observaciones', 'Inspección con novedades')
            ->assertJsonPath('props.factura.numero', sprintf('FAC-%06d', $reservation->id))
            ->assertJsonPath('props.factura.totalCents', 202300000)
            ->assertJsonPath('props.pagoUrl', null)
            ->assertJsonPath('props.puedeCobrar', false);
    });

    it('shows the collect payment link only to cashiers and administrators', function (UsuarioRol $rol) {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Facturada]);
        ActaDevolucion::factory()->create(['reserva_id' => $reservation->id]);
        Factura::factory()->create([
            'reserva_id' => $reservation->id,
            'numero' => 'FAC-COBRO-DEMO',
        ]);

        autenticarEmpleado($rol);

        $this->withHeaders(cabecerasInertiaDevolucion())
            ->get(route('devoluciones.show', $reservation))
            ->assertOk()
            ->assertJsonPath('props.factura.numero', 'FAC-COBRO-DEMO')
            ->assertJsonPath('props.pagoUrl', route('pagos.create', $reservation))
            ->assertJsonPath('props.puedeCobrar', true);
    })->with([
        'cajero' => UsuarioRol::Cajero,
        'administrador' => UsuarioRol::Administrador,
    ]);

    it('shows empty acta details when the return checklist has no values', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Facturada]);
        ActaDevolucion::factory()->create([
            'reserva_id' => $reservation->id,
            'checklist' => null,
            'observaciones' => null,
        ]);
        Factura::factory()->create(['reserva_id' => $reservation->id]);

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaDevolucion())
            ->get(route('devoluciones.show', $reservation))
            ->assertOk()
            ->assertJsonPath('props.acta.kilometraje', null)
            ->assertJsonPath('props.acta.combustible', null)
            ->assertJsonPath('props.acta.observaciones', null);
    });

    it('ignores non numeric mileage and non string fuel in the return summary', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Facturada]);
        ActaDevolucion::factory()->create([
            'reserva_id' => $reservation->id,
            'checklist' => ['kilometraje' => 'n/a', 'combustible' => 2],
        ]);
        Factura::factory()->create(['reserva_id' => $reservation->id]);

        autenticarEmpleado();

        $this->withHeaders(cabecerasInertiaDevolucion())
            ->get(route('devoluciones.show', $reservation))
            ->assertOk()
            ->assertJsonPath('props.acta.kilometraje', null)
            ->assertJsonPath('props.acta.combustible', null);
    });

    it('redirects the success page to the form when the reservation was not returned', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);

        autenticarEmpleado();

        $this->get(route('devoluciones.show', $reservation))
            ->assertRedirectToRoute('devoluciones.create', $reservation);
    });

    it('redirects the success page to the form when the invoice is missing', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Facturada]);
        ActaDevolucion::factory()->create(['reserva_id' => $reservation->id]);

        autenticarEmpleado();

        $this->get(route('devoluciones.show', $reservation))
            ->assertRedirectToRoute('devoluciones.create', $reservation);
    });

    it('forbids a client from opening the return success page', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Facturada]);

        $this->actingAs(User::factory()->cliente()->create())
            ->get(route('devoluciones.show', $reservation))
            ->assertForbidden();
    });
});
