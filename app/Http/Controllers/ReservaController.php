<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Reservas\CrearReserva;
use App\Http\Requests\ConfirmarReservaRequest;
use App\Http\Requests\StoreReservaRequest;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class ReservaController extends Controller
{
    public function index(): Response
    {
        return inertia('Reservas/Index', [
            'reservations' => [],
            'filters' => ['page' => 1],
        ]);
    }

    public function create(): Response
    {
        return inertia('Reservas/Create', [
            'vehicles' => [],
            'quote' => null,
        ]);
    }

    public function store(StoreReservaRequest $request, CrearReserva $crearReserva): RedirectResponse
    {
        $reserva = $crearReserva->handle(
            $request->integer('user_id'),
            $request->integer('vehiculo_id'),
            $request->inicio(),
            $request->fin(),
            $request->convenioId(),
            $request->moneda(),
        );

        return to_route('reservas.show', $reserva);
    }

    public function show(Reserva $reserva): Response
    {
        return inertia('Reservas/Show', [
            'reservation' => $reserva,
        ]);
    }

    public function confirmar(ConfirmarReservaRequest $request, Reserva $reserva): RedirectResponse
    {
        return to_route('reservas.show', $reserva);
    }

    public function cancelar(ConfirmarReservaRequest $request, Reserva $reserva): RedirectResponse
    {
        return to_route('reservas.show', $reserva);
    }
}
