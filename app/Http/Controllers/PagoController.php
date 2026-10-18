<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Pagos\RegistrarPago;
use App\Enums\MetodoPago;
use App\Http\Requests\StorePagoRequest;
use App\Models\Factura;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class PagoController extends Controller
{
    public function create(Reserva $reserva): Response
    {
        $reserva->load(['factura', 'pagos']);
        $factura = $reserva->factura;

        return inertia('Pagos/Create', [
            'reservation' => $reserva,
            'amountCents' => $factura instanceof Factura ? $factura->total_centavos : 0,
            'currency' => $factura instanceof Factura ? $factura->moneda : $reserva->moneda,
            'methods' => ['tarjeta', 'transferencia', 'efectivo'],
            'lastPayment' => $reserva->pagos()->latest('id')->first(),
            'storeUrl' => route('pagos.store', $reserva),
        ]);
    }

    public function store(
        StorePagoRequest $request,
        Reserva $reserva,
        RegistrarPago $registrarPago,
    ): RedirectResponse {
        $registrarPago->handle(
            $reserva,
            $request->enum('metodo', MetodoPago::class),
            $request->integer('monto_centavos'),
            $request->string('numero_tarjeta')->toString() ?: null,
        );

        return to_route('reservas.show', $reserva);
    }
}
