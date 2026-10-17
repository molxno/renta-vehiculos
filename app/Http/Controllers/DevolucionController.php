<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Devoluciones\RegistrarDevolucion;
use App\Contracts\CalculadoraDevolucion;
use App\Enums\NivelCombustible;
use App\Http\Requests\StoreActaDevolucionRequest;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
use Inertia\Response;

final class DevolucionController extends Controller
{
    public function create(Reserva $reserva, CalculadoraDevolucion $calculadora): Response
    {
        $reserva->load(['actaEntrega', 'contrato', 'multas', 'danos']);

        $cobro = $calculadora->calcular($reserva->id, Date::now(), []);

        return inertia('Devoluciones/Create', [
            'reservation' => $reserva,
            'checklist' => $reserva->checklistEntrega(),
            'estimatedChargesCents' => $cobro['cargos_centavos'],
            'cobro' => $cobro,
            'actaEntrega' => $reserva->actaEntrega,
            'storeUrl' => route('devoluciones.store', $reserva),
            'nivelesCombustible' => array_map(
                fn (NivelCombustible $nivel): array => [
                    'value' => $nivel->value,
                    'label' => $nivel->etiqueta(),
                ],
                NivelCombustible::cases(),
            ),
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
            $request->danos(),
        );

        return to_route('reservas.show', $reserva);
    }
}
