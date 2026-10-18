<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Catalogo\ListarCatalogoVehiculos;
use App\Http\Requests\FiltrarCatalogoRequest;
use Inertia\Response;

final class CatalogoController extends Controller
{
    public function __invoke(
        FiltrarCatalogoRequest $request,
        ListarCatalogoVehiculos $listarCatalogoVehiculos,
    ): Response {
        return inertia('Catalogo/Index', $listarCatalogoVehiculos->handle([
            'category' => $request->string('category')->toString(),
            'startDate' => $request->string('startDate')->toString(),
            'endDate' => $request->string('endDate')->toString(),
            'location' => $request->string('location')->toString(),
        ]));
    }
}
