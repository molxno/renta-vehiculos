<?php

declare(strict_types=1);

namespace App\Fakes;

use App\Contracts\TarifaService;
use DateTimeInterface;

final class FakeTarifaService implements TarifaService
{
    public function cotizar(
        int $vehiculoId,
        DateTimeInterface $inicio,
        DateTimeInterface $fin,
        ?int $convenioId = null,
    ): array {
        return [
            'subtotal_centavos' => 45000000,
            'descuento_centavos' => $convenioId === null ? 0 : 4500000,
            'impuestos_centavos' => 8550000,
            'total_centavos' => $convenioId === null ? 53550000 : 49050000,
            'moneda' => 'COP',
        ];
    }
}
