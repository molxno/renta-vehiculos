<?php

declare(strict_types=1);

use App\Enums\FacturaEstado;
use App\Enums\PagoEstado;
use App\Enums\ReservaEstado;
use App\Enums\UsuarioRol;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\User;
use Database\Seeders\PagoRechazadoSeeder;

function cabecerasInertiaPago(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ];
}

describe('listado de pagos', function () {
    it('redirects guests from the payment index to login', function () {
        $this->get(route('pagos.index'))->assertRedirectToRoute('login');
    });

    it('forbids a client from listing pending invoices', function () {
        $this->actingAs(User::factory()->cliente()->create())
            ->get(route('pagos.index'))
            ->assertForbidden();
    });

    it('forbids a coordinator from listing pending invoices', function () {
        autenticarEmpleado(UsuarioRol::Coordinador);

        $this->get(route('pagos.index'))->assertForbidden();
    });

    it('lists pending invoiced reservations for a cashier', function () {
        $this->seed(PagoRechazadoSeeder::class);
        $pendiente = Reserva::query()->where('qr_codigo', 'QR-PAGO-RECHAZADO')->sole();
        Reserva::factory()->create(['estado' => ReservaEstado::Confirmada]);

        autenticarEmpleado(UsuarioRol::Cajero);

        $this->withHeaders(cabecerasInertiaPago())
            ->get(route('pagos.index'))
            ->assertOk()
            ->assertJsonPath('component', 'Pagos/Index')
            ->assertJsonPath('props.invoices.0.reservationId', $pendiente->id)
            ->assertJsonPath('props.invoices.0.invoiceNumber', 'FAC-PAGO-RECHAZADO')
            ->assertJsonPath('props.invoices.0.qrCode', 'QR-PAGO-RECHAZADO')
            ->assertJsonPath('props.invoices.0.clientDocument', '1000000001')
            ->assertJsonPath('props.filters.q', '');
    });

    it('allows an administrator to open the payment index', function () {
        autenticarEmpleado(UsuarioRol::Administrador);

        $this->withHeaders(cabecerasInertiaPago())
            ->get(route('pagos.index'))
            ->assertOk()
            ->assertJsonPath('component', 'Pagos/Index');
    });

    it('finds a pending invoice by number, reservation id or QR', function (string $campo) {
        $this->seed(PagoRechazadoSeeder::class);
        $pendiente = Reserva::query()->where('qr_codigo', 'QR-PAGO-RECHAZADO')->sole();
        Factura::factory()->create([
            'estado' => FacturaEstado::Pendiente,
            'reserva_id' => Reserva::factory()->create([
                'estado' => ReservaEstado::Facturada,
                'qr_codigo' => 'QR-OTRA-FACTURADA',
            ])->id,
        ]);

        $termino = match ($campo) {
            'numero' => 'FAC-PAGO-RECHAZADO',
            'id' => (string) $pendiente->id,
            'qr' => 'QR-PAGO-RECHAZADO',
            default => 'FAKE-QR-'.$pendiente->id,
        };

        autenticarEmpleado(UsuarioRol::Cajero);

        $this->withHeaders(cabecerasInertiaPago())
            ->get(route('pagos.index', ['q' => $termino]))
            ->assertOk()
            ->assertJsonPath('props.filters.q', $termino)
            ->assertJsonPath('props.invoices.0.reservationId', $pendiente->id)
            ->assertJsonCount(1, 'props.invoices');
    })->with(['numero', 'id', 'qr', 'fake-qr']);

    it('hides paid invoices from the cashier list', function () {
        $pagada = Reserva::factory()->create(['estado' => ReservaEstado::Pagada]);
        Factura::factory()->create([
            'reserva_id' => $pagada->id,
            'estado' => FacturaEstado::Pagada,
            'numero' => 'FAC-YA-PAGADA',
        ]);

        autenticarEmpleado(UsuarioRol::Cajero);

        $this->withHeaders(cabecerasInertiaPago())
            ->get(route('pagos.index'))
            ->assertOk()
            ->assertJsonPath('props.invoices', []);
    });
});

describe('formulario y éxito de pago', function () {
    it('forbids a coordinator from opening the payment form', function () {
        $reservation = Reserva::factory()->create();

        autenticarEmpleado(UsuarioRol::Coordinador);

        $this->get(route('pagos.create', $reservation))->assertForbidden();
        $this->post(route('pagos.store', $reservation), [])->assertForbidden();
        $this->get(route('pagos.show', $reservation))->assertForbidden();
    });

    it('shows zero amount when the reservation has no invoice', function () {
        $reservation = Reserva::factory()->create([
            'estado' => ReservaEstado::Facturada,
            'moneda' => 'COP',
        ]);

        autenticarEmpleado(UsuarioRol::Cajero);

        $this->withHeaders(cabecerasInertiaPago())
            ->get(route('pagos.create', $reservation))
            ->assertOk()
            ->assertJsonPath('props.amountCents', 0)
            ->assertJsonPath('props.invoice', null)
            ->assertJsonPath('props.currency', 'COP');
    });

    it('shows the paid invoice receipt after a successful collection', function () {
        $reservation = Reserva::factory()->create([
            'estado' => ReservaEstado::Pagada,
            'qr_codigo' => 'QR-PAGO-APROBADO',
        ]);
        $invoice = Factura::factory()->create([
            'reserva_id' => $reservation->id,
            'numero' => 'FAC-PAGO-APROBADO',
            'estado' => FacturaEstado::Pagada,
            'total_centavos' => 53550000,
        ]);
        Pago::factory()->create([
            'reserva_id' => $reservation->id,
            'estado' => PagoEstado::Aprobado,
            'referencia' => 'FAKE-EFECTIVO-'.$reservation->id,
            'monto_centavos' => 53550000,
        ]);

        autenticarEmpleado(UsuarioRol::Cajero);

        $this->withHeaders(cabecerasInertiaPago())
            ->get(route('pagos.show', $reservation))
            ->assertOk()
            ->assertJsonPath('component', 'Pagos/Show')
            ->assertJsonPath('props.reservation.status', ReservaEstado::Pagada->value)
            ->assertJsonPath('props.invoice.numero', 'FAC-PAGO-APROBADO')
            ->assertJsonPath('props.payment.referencia', 'FAKE-EFECTIVO-'.$reservation->id)
            ->assertJsonPath('props.facturaUrl', route('facturas.show', $invoice));
    });

    it('redirects the success page to the form when the invoice is still pending', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Facturada]);
        Factura::factory()->create([
            'reserva_id' => $reservation->id,
            'estado' => FacturaEstado::Pendiente,
        ]);

        autenticarEmpleado(UsuarioRol::Cajero);

        $this->get(route('pagos.show', $reservation))
            ->assertRedirectToRoute('pagos.create', $reservation);
    });
});
