<?php

namespace App\Contracts;

use DateTimeInterface;

interface DisponibilidadService
{
    /**
     * @return array<int, array{
     *     vehiculo_id: int,
     *     disponible: bool,
     *     motivo: string|null
     * }>
     */
    public function consultar(
        DateTimeInterface $inicio,
        DateTimeInterface $fin,
        ?int $vehiculoId = null,
    ): array;
}
