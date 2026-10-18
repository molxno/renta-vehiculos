<?php

use App\Contracts\CalculadoraDevolucion;
use App\Contracts\DisponibilidadService;
use App\Contracts\GeneradorQr;
use App\Contracts\PasarelaPago;
use App\Contracts\TarifaService;
use App\Enums\FacturaEstado;
use App\Enums\MetodoPago;
use App\Enums\PagoEstado;
use App\Enums\ReservaEstado;
use App\Models\ActaDevolucion;
use App\Models\ActaEntrega;
use App\Models\Contrato;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\Tarifa;
use App\Models\Vehiculo;
use Illuminate\Support\Carbon;

test('completes the reservation delivery return and payment contract flow', function () {
    $vehicle = Vehiculo::factory()->create();
    Tarifa::factory()->create([
        'vehiculo_id' => $vehicle->id,
        'tipo_vehiculo' => $vehicle->tipo,
        'vigente_desde' => '2026-01-01',
    ]);
    $reservationStart = Carbon::parse('2026-10-13 09:00:00');
    $reservationEnd = Carbon::parse('2026-10-15 09:00:00');
    $availability = app(DisponibilidadService::class)->consultar(
        $reservationStart,
        $reservationEnd,
        $vehicle->id,
    );

    $reservation = Reserva::factory()->create([
        'estado' => ReservaEstado::Confirmada,
        'total_centavos' => 53550000,
        'vehiculo_id' => $vehicle->id,
        'inicio' => $reservationStart,
        'fin' => $reservationEnd,
    ]);

    $quote = app(TarifaService::class)->cotizar(
        $reservation->vehiculo_id,
        $reservation->inicio,
        $reservation->fin,
    );
    $reservation->update([
        'total_centavos' => $quote['total_centavos'],
        'qr_codigo' => app(GeneradorQr::class)->generar($reservation->id),
    ]);

    $contract = Contrato::factory()->create(['reserva_id' => $reservation->id]);
    $delivery = ActaEntrega::factory()->create(['reserva_id' => $reservation->id]);
    $reservation->update(['estado' => ReservaEstado::Entregada]);

    $returnAt = Carbon::parse('2026-10-15 09:00:00');
    $charges = app(CalculadoraDevolucion::class)->calcular(
        $reservation->id,
        $returnAt,
        $delivery->checklist ?? [],
    );
    $return = ActaDevolucion::factory()->create([
        'reserva_id' => $reservation->id,
        'devuelto_at' => $returnAt,
    ]);
    $reservation->update(['estado' => ReservaEstado::Devuelta]);

    $invoice = Factura::factory()->create([
        'reserva_id' => $reservation->id,
        'estado' => FacturaEstado::Pendiente,
        'subtotal_centavos' => $charges['subtotal_centavos'],
        'impuestos_centavos' => $charges['impuestos_centavos'],
        'total_centavos' => $charges['total_centavos'],
    ]);
    $reservation->update(['estado' => ReservaEstado::Facturada]);

    $authorization = app(PasarelaPago::class)->autorizar(
        $reservation->id,
        $invoice->total_centavos,
        '4111111111111111',
    );
    $payment = Pago::factory()->create([
        'reserva_id' => $reservation->id,
        'estado' => $authorization['aprobado']
            ? PagoEstado::Aprobado
            : PagoEstado::Rechazado,
        'metodo' => MetodoPago::Tarjeta,
        'monto_centavos' => $invoice->total_centavos,
        'referencia' => $authorization['referencia'],
        'mensaje' => $authorization['mensaje'],
    ]);
    $invoice->update(['estado' => FacturaEstado::Pagada]);
    $reservation->update(['estado' => ReservaEstado::Pagada]);

    expect($availability)->toHaveCount(1)
        ->and($availability[0]['disponible'])->toBeTrue()
        ->and($contract->reserva_id)->toBe($reservation->id)
        ->and($return->reserva_id)->toBe($reservation->id)
        ->and($payment->estado)->toBe(PagoEstado::Aprobado)
        ->and($invoice->fresh()->estado)->toBe(FacturaEstado::Pagada)
        ->and($reservation->fresh()->estado)->toBe(ReservaEstado::Pagada);
})->group('flow');
