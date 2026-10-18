<?php

declare(strict_types=1);

namespace App\Actions\Catalogo;

use App\Contracts\DisponibilidadService;
use App\Models\Tarifa;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Date;

final class ListarCatalogoVehiculos
{
    public function __construct(private DisponibilidadService $disponibilidad) {}

    /**
     * @param  array{
     *     category?: string|null,
     *     startDate?: string|null,
     *     endDate?: string|null,
     *     location?: string|null
     * }  $filters
     * @return array{
     *     vehicles: list<array{
     *         id: int,
     *         label: string,
     *         brand: string,
     *         model: string,
     *         year: int,
     *         category: string,
     *         status: string,
     *         dailyRateCents: int
     *     }>,
     *     categories: list<string>,
     *     filters: array{category: string, startDate: string, endDate: string, location: string}
     * }
     */
    public function handle(array $filters): array
    {
        $category = trim((string) ($filters['category'] ?? ''));
        $startDate = trim((string) ($filters['startDate'] ?? ''));
        $endDate = trim((string) ($filters['endDate'] ?? ''));
        $location = trim((string) ($filters['location'] ?? ''));

        $categories = array_values(
            Vehiculo::query()
                ->select('tipo')
                ->distinct()
                ->orderBy('tipo')
                ->pluck('tipo')
                ->map(fn (mixed $tipo): string => (string) $tipo)
                ->all(),
        );

        $vehiculos = Vehiculo::query()
            ->with(['tarifas' => function (HasMany $relation): void {
                $relation->where(function (Builder $query): void {
                    $query->whereNull('vigente_hasta')
                        ->orWhereDate('vigente_hasta', '>=', now()->toDateString());
                })->orderByDesc('vigente_desde');
            }])
            ->when($category !== '', fn (Builder $query) => $query->where('tipo', $category))
            ->orderBy('id')
            ->get();

        if ($startDate !== '' && $endDate !== '') {
            $idsDisponibles = [];

            foreach ($this->disponibilidad->consultar(
                Date::parse($startDate)->startOfDay(),
                Date::parse($endDate)->startOfDay(),
            ) as $disponibilidad) {
                if ($disponibilidad['disponible'] === true) {
                    $idsDisponibles[] = $disponibilidad['vehiculo_id'];
                }
            }

            $vehiculos = $vehiculos
                ->filter(fn (Vehiculo $vehiculo): bool => in_array($vehiculo->id, $idsDisponibles, true))
                ->values();
        }

        $vehicles = [];

        foreach ($vehiculos as $vehiculo) {
            $tarifa = $vehiculo->tarifas->first();

            $vehicles[] = [
                'id' => $vehiculo->id,
                'label' => trim($vehiculo->marca.' '.$vehiculo->modelo),
                'brand' => $vehiculo->marca,
                'model' => $vehiculo->modelo,
                'year' => $vehiculo->anio,
                'category' => $vehiculo->tipo,
                'status' => $vehiculo->estado->value,
                'dailyRateCents' => $tarifa instanceof Tarifa
                    ? $tarifa->valor_diario_centavos
                    : 0,
            ];
        }

        return [
            'vehicles' => $vehicles,
            'categories' => $categories,
            'filters' => [
                'category' => $category,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'location' => $location,
            ],
        ];
    }
}
