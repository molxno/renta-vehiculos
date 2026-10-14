<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActaDevolucionRequest;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class DevolucionController extends Controller
{
    public function create(Reserva $reserva): Response
    {
        return inertia('Devoluciones/Create', [
            'reservation' => $reserva,
            'checklist' => [],
            'estimatedChargesCents' => 0,
        ]);
    }

    public function store(StoreActaDevolucionRequest $request, Reserva $reserva): RedirectResponse
    {
        return to_route('reservas.show', $reserva);
    }
}
