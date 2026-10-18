<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Reserva;
use DateTimeInterface;

interface CargoDevolucionStrategy
{
    /**
     * @param  array<string, mixed>  $condiciones
     * @return array<int, array{concepto: string, monto_centavos: int}>
     */
    public function lineas(
        Reserva $reserva,
        DateTimeInterface $devueltoEn,
        array $condiciones,
    ): array;
}
