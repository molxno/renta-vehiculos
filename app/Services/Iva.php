<?php

declare(strict_types=1);

namespace App\Services;

final class Iva
{
    public static function porcentaje(): int
    {
        return (int) config('devolucion.iva_porcentaje');
    }

    public static function de(int $baseCentavos): int
    {
        return intdiv(($baseCentavos * self::porcentaje()) + 50, 100);
    }
}
