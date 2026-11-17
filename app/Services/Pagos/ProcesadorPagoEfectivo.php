<?php

declare(strict_types=1);

namespace App\Services\Pagos;

use App\Contracts\ProcesadorPago;
use App\Models\Reserva;

final class ProcesadorPagoEfectivo implements ProcesadorPago
{
    public function procesar(
        Reserva $reserva,
        int $montoCentavos,
        ?string $numeroTarjeta,
    ): ResultadoPago {
        return new ResultadoPago(
            true,
            'FAKE-EFECTIVO-'.$reserva->id,
            'Pago autorizado',
            '00',
        );
    }
}
