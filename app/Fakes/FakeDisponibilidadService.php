<?php

declare(strict_types=1);

namespace App\Fakes;

use App\Contracts\DisponibilidadService;
use App\Enums\ReservaEstado;
use App\Enums\VehiculoEstado;
use App\Models\Reserva;
use App\Models\Vehiculo;
use DateTimeInterface;

final class FakeDisponibilidadService implements DisponibilidadService
{
    public function consultar(
        DateTimeInterface $inicio,
        DateTimeInterface $fin,
        ?int $vehiculoId = null,
    ): array {
        $vehiculosOcupados = Reserva::query()
            ->where('estado', '!=', ReservaEstado::Cancelada->value)
            ->where('inicio', '<', $fin)
            ->where('fin', '>', $inicio)
            ->when($vehiculoId !== null, fn ($query) => $query->where('vehiculo_id', $vehiculoId))
            ->pluck('vehiculo_id')
            ->all();

        $resultados = Vehiculo::query()
            ->when($vehiculoId !== null, fn ($query) => $query->whereKey($vehiculoId))
            ->orderBy('id')
            ->get(['id', 'estado'])
            ->map(function (Vehiculo $vehiculo) use ($vehiculosOcupados): array {
                $estaOcupado = in_array($vehiculo->id, $vehiculosOcupados, true);
                $estaDisponible = $vehiculo->estado === VehiculoEstado::Disponible && ! $estaOcupado;

                return [
                    'vehiculo_id' => $vehiculo->id,
                    'disponible' => $estaDisponible,
                    'motivo' => match (true) {
                        $estaOcupado => 'El vehículo tiene una reserva para esas fechas.',
                        $vehiculo->estado !== VehiculoEstado::Disponible => 'El vehículo no está disponible para renta.',
                        default => null,
                    },
                ];
            })
            ->all();

        if (collect($resultados)->contains(fn (array $resultado): bool => $resultado['disponible'])) {
            return $resultados;
        }

        return array_map(
            fn (array $resultado): array => [
                ...$resultado,
                'motivo' => $resultado['motivo'].' Modifica las fechas o selecciona otro vehículo.',
            ],
            $resultados,
        );
    }
}
