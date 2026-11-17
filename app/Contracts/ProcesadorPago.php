<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Reserva;
use App\Services\Pagos\ResultadoPago;

interface ProcesadorPago
{
    public function procesar(
        Reserva $reserva,
        int $montoCentavos,
        ?string $numeroTarjeta,
    ): ResultadoPago;
}
