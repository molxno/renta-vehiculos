<?php

namespace App\Fakes;

use App\Contracts\DisponibilidadService;
use App\Models\Vehiculo;
use DateTimeInterface;

class FakeDisponibilidadService implements DisponibilidadService
{
    public function consultar(
        DateTimeInterface $inicio,
        DateTimeInterface $fin,
        ?int $vehiculoId = null,
    ): array {
        return Vehiculo::query()
            ->when($vehiculoId !== null, fn ($query) => $query->whereKey($vehiculoId))
            ->orderBy('id')
            ->get(['id'])
            ->map(fn (Vehiculo $vehiculo): array => [
                'vehiculo_id' => $vehiculo->id,
                'disponible' => true,
                'motivo' => null,
            ])
            ->all();
    }
}
