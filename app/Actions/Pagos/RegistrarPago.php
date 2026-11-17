<?php

declare(strict_types=1);

namespace App\Actions\Pagos;

use App\Enums\FacturaEstado;
use App\Enums\MetodoPago;
use App\Enums\PagoEstado;
use App\Models\Pago;
use App\Models\Reserva;
use App\Services\Pagos\FabricaProcesadorPago;
use App\States\Reserva\FacturadaState;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RegistrarPago
{
    public function __construct(private FabricaProcesadorPago $fabrica) {}

    public function handle(
        Reserva $reserva,
        MetodoPago $metodo,
        int $montoCentavos,
        ?string $numeroTarjeta,
    ): Pago {
        $pago = DB::transaction(function () use ($reserva, $metodo, $montoCentavos, $numeroTarjeta): Pago {
            $reserva->load(['factura', 'pagos']);
            $factura = $reserva->factura;

            if ($factura === null || $factura->estado !== FacturaEstado::Pendiente) {
                throw ValidationException::withMessages([
                    'monto_centavos' => 'La reserva no tiene una factura pendiente.',
                ]);
            }

            if (! $reserva->estadoMaquina() instanceof FacturadaState) {
                throw ValidationException::withMessages([
                    'monto_centavos' => 'Solo se puede pagar una reserva facturada.',
                ]);
            }

            $saldoCentavos = $reserva->saldoAPagarCentavos($factura->total_centavos);

            if ($montoCentavos !== $saldoCentavos) {
                throw ValidationException::withMessages([
                    'monto_centavos' => 'El monto no coincide con el saldo a pagar.',
                ]);
            }

            $resultado = $this->fabrica
                ->crear($metodo)
                ->procesar($reserva, $montoCentavos, $numeroTarjeta);

            $pago = $reserva->pagos()->create([
                'estado' => $resultado->aprobado ? PagoEstado::Aprobado : PagoEstado::Rechazado,
                'metodo' => $metodo,
                'monto_centavos' => $montoCentavos,
                'referencia' => $resultado->referencia,
                'mensaje' => $resultado->mensaje,
            ]);

            if ($resultado->aprobado) {
                $reserva->unsetRelation('pagos');

                if ($reserva->saldoAPagarCentavos($factura->total_centavos) === 0) {
                    $factura->update(['estado' => FacturaEstado::Pagada]);
                    $reserva->pagar();
                }
            }

            return $pago;
        });

        if ($pago->estado === PagoEstado::Rechazado) {
            throw ValidationException::withMessages([
                'numero_tarjeta' => $pago->mensaje ?? 'Pago rechazado',
            ]);
        }

        return $pago;
    }
}
