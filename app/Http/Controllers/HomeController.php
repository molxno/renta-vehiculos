<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Catalogo\ListarVehiculosDestacados;
use App\Enums\VehiculoEstado;
use App\Models\Vehiculo;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __invoke(ListarVehiculosDestacados $listarVehiculosDestacados): Response
    {
        return inertia('Welcome', [
            'featuredVehicles' => $listarVehiculosDestacados->handle(),
            'vehiclesAvailableCount' => Vehiculo::query()
                ->where('estado', VehiculoEstado::Disponible)
                ->count(),
            'search' => [
                'location' => '',
                'startDate' => '',
                'endDate' => '',
            ],
        ]);
    }
}
