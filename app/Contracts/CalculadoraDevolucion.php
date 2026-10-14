<?php

namespace App\Contracts;

use DateTimeInterface;

interface CalculadoraDevolucion
{
    /**
     * @param  array<string, mixed>  $condiciones
     * @return array{
     *     cargos_centavos: int,
     *     subtotal_centavos: int,
     *     impuestos_centavos: int,
     *     total_centavos: int,
     *     desglose: array<int, array{
     *         concepto: string,
     *         monto_centavos: int
     *     }>
     * }
     */
    public function calcular(
        int $reservaId,
        DateTimeInterface $devueltoEn,
        array $condiciones = [],
    ): array;
}
