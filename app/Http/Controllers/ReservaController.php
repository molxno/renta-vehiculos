<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Reservas\ConfirmarReserva as ConfirmarReservaAction;
use App\Actions\Reservas\CrearReserva;
use App\Http\Requests\ConfirmarReservaRequest;
use App\Http\Requests\StoreReservaRequest;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
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
        $cliente = $request->user();

        if (! $cliente instanceof User) {
            abort(403);
        }

        $reserva = $crearReserva->handle(
            $cliente,
            $request->integer('vehiculo_id'),
            $request->filled('convenio_id') ? $request->integer('convenio_id') : null,
            Carbon::parse($request->string('inicio')->toString()),
            Carbon::parse($request->string('fin')->toString()),
        );

        return to_route('reservas.show', $reserva);
    }

    public function show(Reserva $reserva): Response
    {
        return inertia('Reservas/Show', [
            'reservation' => $reserva,
        ]);
    }

    public function showByQrCode(string $qrCodigo): RedirectResponse
    {
        $reserva = Reserva::query()->where('qr_codigo', $qrCodigo)->firstOrFail();

        return to_route('reservas.show', $reserva);
    }

    public function confirmar(
        ConfirmarReservaRequest $request,
        Reserva $reserva,
        ConfirmarReservaAction $confirmarReserva,
    ): RedirectResponse {
        $confirmarReserva->handle($reserva);

        return to_route('reservas.show', $reserva);
    }

    public function cancelar(ConfirmarReservaRequest $request, Reserva $reserva): RedirectResponse
    {
        return to_route('reservas.show', $reserva);
    }
}
