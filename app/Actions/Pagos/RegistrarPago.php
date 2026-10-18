<?php

declare(strict_types=1);

namespace App\Actions\Pagos;

use App\Contracts\PasarelaPago;
use App\Enums\FacturaEstado;
use App\Enums\MetodoPago;
use App\Enums\PagoEstado;
use App\Enums\ReservaEstado;
use App\Models\Pago;
use App\Models\Reserva;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RegistrarPago
{
    public function __construct(private PasarelaPago $pasarela) {}

    public function handle(
        Reserva $reserva,
        MetodoPago $metodo,
        int $montoCentavos,
        ?string $numeroTarjeta,
    ): Pago {
        $pago = DB::transaction(function () use ($reserva, $metodo, $montoCentavos, $numeroTarjeta): Pago {
            $factura = $reserva->factura;

            if ($factura === null || $factura->estado !== FacturaEstado::Pendiente) {
                throw ValidationException::withMessages([
                    'monto_centavos' => 'La reserva no tiene una factura pendiente.',
                ]);
            }

            if ($reserva->estado !== ReservaEstado::Facturada) {
                throw ValidationException::withMessages([
                    'monto_centavos' => 'Solo se puede pagar una reserva facturada.',
                ]);
            }

            if ($montoCentavos !== $factura->total_centavos) {
                throw ValidationException::withMessages([
                    'monto_centavos' => 'El monto no coincide con el total de la factura.',
                ]);
            }

            $resultado = $metodo === MetodoPago::Tarjeta
                ? $this->pasarela->autorizar($reserva->id, $montoCentavos, (string) $numeroTarjeta)
                : [
                    'aprobado' => true,
                    'referencia' => 'FAKE-'.strtoupper($metodo->value).'-'.$reserva->id,
                    'mensaje' => 'Pago autorizado',
                    'codigo' => '00',
                ];

            $pago = $reserva->pagos()->create([
                'estado' => $resultado['aprobado'] ? PagoEstado::Aprobado : PagoEstado::Rechazado,
                'metodo' => $metodo,
                'monto_centavos' => $montoCentavos,
                'referencia' => $resultado['referencia'],
                'mensaje' => $resultado['mensaje'],
            ]);

            if ($resultado['aprobado']) {
                $factura->update(['estado' => FacturaEstado::Pagada]);
                $reserva->update(['estado' => ReservaEstado::Pagada]);
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
