<?php

declare(strict_types=1);

namespace App\Actions\Catalogo;

use App\Enums\VehiculoEstado;
use App\Models\Tarifa;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ListarVehiculosDestacados
{
    /**
     * @return list<array{id: int, label: string, category: string, dailyRateCents: int}>
     */
    public function handle(): array
    {
        $vehiculos = Vehiculo::query()
            ->where('estado', VehiculoEstado::Disponible)
            ->with(['tarifas' => function (HasMany $relation): void {
                $relation->where(function (Builder $query): void {
                    $query->whereNull('vigente_hasta')
                        ->orWhereDate('vigente_hasta', '>=', now()->toDateString());
                })->orderByDesc('vigente_desde');
            }])
            ->orderBy('id')
            ->limit(6)
            ->get();

        $destacados = [];

        foreach ($vehiculos as $vehiculo) {
            $tarifa = $vehiculo->tarifas->first();

            $destacados[] = [
                'id' => $vehiculo->id,
                'label' => trim($vehiculo->marca.' '.$vehiculo->modelo),
                'category' => $vehiculo->tipo,
                'dailyRateCents' => $tarifa instanceof Tarifa
                    ? $tarifa->valor_diario_centavos
                    : 0,
            ];
        }

        return $destacados;
    }
}
