<?php

use App\Enums\FacturaEstado;
use App\Enums\MetodoPago;
use App\Enums\PagoEstado;
use App\Enums\ReservaEstado;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\Reserva;
use Database\Seeders\PagoRechazadoSeeder;

function facturaPendiente(): array
{
    $reservation = Reserva::factory()->create([
        'estado' => ReservaEstado::Facturada,
    ]);
    $invoice = Factura::factory()->create([
        'reserva_id' => $reservation->id,
        'estado' => FacturaEstado::Pendiente,
        'total_centavos' => 53550000,
        'moneda' => 'COP',
    ]);

    return [$reservation->fresh(), $invoice];
}

function inertiaPagoHeaders(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ];
}

describe('registro de pago', function () {
    beforeEach(function (): void {
        autenticarEmpleado();
    });

    it('renders the payment page with the pending invoice total', function () {
        [$reservation, $invoice] = facturaPendiente();

        $this->withHeaders(inertiaPagoHeaders())
            ->get(route('pagos.create', $reservation))
            ->assertOk()
            ->assertJsonPath('component', 'Pagos/Create')
            ->assertJsonPath('props.amountCents', $invoice->total_centavos)
            ->assertJsonPath('props.currency', 'COP');
    });

    it('authorizes a card that the fake gateway accepts and marks the invoice paid', function () {
        [$reservation, $invoice] = facturaPendiente();

        $this->from(route('pagos.create', $reservation))
            ->post(route('pagos.store', $reservation), [
                'metodo' => MetodoPago::Tarjeta->value,
                'monto_centavos' => $invoice->total_centavos,
                'numero_tarjeta' => '4111111111111111',
            ])
            ->assertRedirectToRoute('reservas.show', $reservation);

        $payment = Pago::query()->where('reserva_id', $reservation->id)->sole();

        expect($payment->estado)->toBe(PagoEstado::Aprobado)
            ->and($payment->referencia)->toBe('FAKE-PAY-'.$reservation->id)
            ->and($invoice->fresh()->estado)->toBe(FacturaEstado::Pagada)
            ->and($reservation->fresh()->estado)->toBe(ReservaEstado::Pagada);
    });

    it('rejects a card ending in 0000 and keeps the invoice pending', function () {
        [$reservation, $invoice] = facturaPendiente();

        $this->from(route('pagos.create', $reservation))
            ->post(route('pagos.store', $reservation), [
                'metodo' => MetodoPago::Tarjeta->value,
                'monto_centavos' => $invoice->total_centavos,
                'numero_tarjeta' => '4111111111110000',
            ])
            ->assertRedirect(route('pagos.create', $reservation))
            ->assertSessionHasErrors([
                'numero_tarjeta' => 'Pago rechazado',
            ]);

        $payment = Pago::query()->where('reserva_id', $reservation->id)->sole();

        expect($payment->estado)->toBe(PagoEstado::Rechazado)
            ->and($payment->referencia)->toBeNull()
            ->and($invoice->fresh()->estado)->toBe(FacturaEstado::Pendiente)
            ->and($reservation->fresh()->estado)->toBe(ReservaEstado::Facturada);
    });

    it('retries after a rejected payment and then approves', function () {
        $this->seed(PagoRechazadoSeeder::class);
        $reservation = Reserva::query()->where('qr_codigo', 'QR-PAGO-RECHAZADO')->sole();
        $invoice = $reservation->factura;

        $this->from(route('pagos.create', $reservation))
            ->post(route('pagos.store', $reservation), [
                'metodo' => MetodoPago::Tarjeta->value,
                'monto_centavos' => $invoice->total_centavos,
                'numero_tarjeta' => '5555555555554444',
            ])
            ->assertRedirectToRoute('reservas.show', $reservation);

        expect(Pago::query()->where('reserva_id', $reservation->id)->count())->toBe(2)
            ->and($invoice->fresh()->estado)->toBe(FacturaEstado::Pagada)
            ->and($reservation->fresh()->estado)->toBe(ReservaEstado::Pagada)
            ->and(Pago::query()->where('reserva_id', $reservation->id)->where('estado', PagoEstado::Aprobado)->exists())->toBeTrue();
    });

    it('approves cash without calling a card number', function () {
        [$reservation, $invoice] = facturaPendiente();

        $this->post(route('pagos.store', $reservation), [
            'metodo' => MetodoPago::Efectivo->value,
            'monto_centavos' => $invoice->total_centavos,
        ])->assertRedirectToRoute('reservas.show', $reservation);

        expect(Pago::query()->where('reserva_id', $reservation->id)->sole()->estado)->toBe(PagoEstado::Aprobado)
            ->and($invoice->fresh()->estado)->toBe(FacturaEstado::Pagada);
    });

    it('rejects a payment when the reservation is not invoiced', function () {
        [$reservation, $invoice] = facturaPendiente();
        $reservation->update(['estado' => ReservaEstado::Devuelta]);

        $this->from(route('pagos.create', $reservation))
            ->post(route('pagos.store', $reservation), [
                'metodo' => MetodoPago::Efectivo->value,
                'monto_centavos' => $invoice->total_centavos,
            ])
            ->assertRedirect(route('pagos.create', $reservation))
            ->assertSessionHasErrors([
                'monto_centavos' => 'Solo se puede pagar una reserva facturada.',
            ]);

        expect(Pago::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse()
            ->and($invoice->fresh()->estado)->toBe(FacturaEstado::Pendiente)
            ->and($reservation->fresh()->estado)->toBe(ReservaEstado::Devuelta);
    });

    it('rejects a payment when the reservation has no invoice', function () {
        $reservation = Reserva::factory()->create([
            'estado' => ReservaEstado::Facturada,
        ]);

        $this->from(route('pagos.create', $reservation))
            ->post(route('pagos.store', $reservation), [
                'metodo' => MetodoPago::Efectivo->value,
                'monto_centavos' => 100,
            ])
            ->assertRedirect(route('pagos.create', $reservation))
            ->assertSessionHasErrors([
                'monto_centavos' => 'La reserva no tiene una factura pendiente.',
            ]);

        expect(Pago::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse();
    });

    it('rejects an amount that does not match the invoice', function () {
        [$reservation, $invoice] = facturaPendiente();

        $this->from(route('pagos.create', $reservation))
            ->post(route('pagos.store', $reservation), [
                'metodo' => MetodoPago::Efectivo->value,
                'monto_centavos' => $invoice->total_centavos - 1,
            ])
            ->assertRedirect(route('pagos.create', $reservation))
            ->assertSessionHasErrors('monto_centavos');

        expect(Pago::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse()
            ->and($invoice->fresh()->estado)->toBe(FacturaEstado::Pendiente);
    });

    it('rejects a second successful payment', function () {
        [$reservation, $invoice] = facturaPendiente();

        $this->post(route('pagos.store', $reservation), [
            'metodo' => MetodoPago::Efectivo->value,
            'monto_centavos' => $invoice->total_centavos,
        ])->assertRedirectToRoute('reservas.show', $reservation);

        $this->from(route('pagos.create', $reservation))
            ->post(route('pagos.store', $reservation), [
                'metodo' => MetodoPago::Efectivo->value,
                'monto_centavos' => $invoice->total_centavos,
            ])
            ->assertRedirect(route('pagos.create', $reservation))
            ->assertSessionHasErrors('monto_centavos');

        expect(Pago::query()->where('reserva_id', $reservation->id)->count())->toBe(1);
    });
});
