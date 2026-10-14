<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreActaEntregaRequest;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class EntregaController extends Controller
{
    public function create(Reserva $reserva): Response
    {
        return inertia('Entregas/Create', [
            'reservation' => $reserva,
            'checklist' => [],
        ]);
    }

    public function store(StoreActaEntregaRequest $request, Reserva $reserva): RedirectResponse
    {
        return to_route('reservas.show', $reserva);
    }
}
