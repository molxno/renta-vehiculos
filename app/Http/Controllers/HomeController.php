<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Catalogo\ListarVehiculosDestacados;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __invoke(ListarVehiculosDestacados $listarVehiculosDestacados): Response
    {
        return inertia('Welcome', [
            'featuredVehicles' => $listarVehiculosDestacados->handle(),
            'search' => [
                'location' => '',
                'startDate' => '',
                'endDate' => '',
            ],
        ]);
    }
}
