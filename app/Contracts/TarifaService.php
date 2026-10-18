<?php

declare(strict_types=1);

namespace App\Contracts;

use DateTimeInterface;

interface TarifaService
{
    /**
     * @return array{
     *     subtotal_centavos: int,
     *     descuento_centavos: int,
     *     impuestos_centavos: int,
     *     total_centavos: int,
     *     moneda: string
     * }
     */
    public function cotizar(
        int $vehiculoId,
        DateTimeInterface $inicio,
        DateTimeInterface $fin,
        ?int $convenioId = null,
        ?string $moneda = null,
    ): array;
}
