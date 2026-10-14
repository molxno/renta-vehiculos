<?php

declare(strict_types=1);

namespace App\Fakes;

use App\Contracts\CalculadoraDevolucion;
use DateTimeInterface;

final class FakeCalculadoraDevolucion implements CalculadoraDevolucion
{
    public function calcular(
        int $reservaId,
        DateTimeInterface $devueltoEn,
        array $condiciones = [],
    ): array {
        return [
            'cargos_centavos' => 0,
            'subtotal_centavos' => 45000000,
            'impuestos_centavos' => 8550000,
            'total_centavos' => 53550000,
            'desglose' => [
                [
                    'concepto' => 'Renta base',
                    'monto_centavos' => 45000000,
                ],
            ],
        ];
    }
}
