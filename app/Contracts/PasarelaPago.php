<?php

namespace App\Contracts;

interface PasarelaPago
{
    /**
     * @return array{
     *     aprobado: bool,
     *     referencia: string|null,
     *     mensaje: string,
     *     codigo: string
     * }
     */
    public function autorizar(
        int $reservaId,
        int $montoCentavos,
        string $numeroTarjeta,
    ): array;
}
