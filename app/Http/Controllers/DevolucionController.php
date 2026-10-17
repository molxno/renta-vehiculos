<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Devoluciones\RegistrarDevolucion;
use App\Http\Requests\StoreActaDevolucionRequest;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class DevolucionController extends Controller
{
    public function create(Reserva $reserva): Response
    {
        return inertia('Devoluciones/Create', [
            'reservation' => $reserva,
            'checklist' => [],
            'estimatedChargesCents' => 0,
        ]);
    }

    public function store(
        StoreActaDevolucionRequest $request,
        Reserva $reserva,
        RegistrarDevolucion $registrarDevolucion,
    ): RedirectResponse {
        $registrarDevolucion->handle(
            $reserva,
            $request->devueltoAt(),
            $request->checklist(),
            $request->observaciones(),
        );

        return to_route('reservas.show', $reserva);
    }
}
