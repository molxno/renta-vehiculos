<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePagoRequest;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class PagoController extends Controller
{
    public function create(Reserva $reserva): Response
    {
        return inertia('Pagos/Create', [
            'reservation' => $reserva,
            'amountCents' => 0,
            'currency' => 'COP',
            'methods' => ['tarjeta', 'transferencia', 'efectivo'],
        ]);
    }

    public function store(StorePagoRequest $request, Reserva $reserva): RedirectResponse
    {
        return to_route('reservas.show', $reserva);
    }
}
