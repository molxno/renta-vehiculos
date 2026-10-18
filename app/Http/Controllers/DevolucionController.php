<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Devoluciones\RegistrarDevolucion;
use App\Contracts\CalculadoraDevolucion;
use App\Enums\NivelCombustible;
use App\Http\Requests\StoreActaDevolucionRequest;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Response;

final class DevolucionController extends Controller
{
    public function create(Request $request, Reserva $reserva, CalculadoraDevolucion $calculadora): Response
    {
        $reserva->load(['actaEntrega', 'contrato', 'multas', 'danos']);

        $devueltoEn = $request->filled('devuelto_at')
            ? Date::parse($request->string('devuelto_at')->toString())
            : Date::now();

        $kilometraje = $request->input('kilometraje', $request->input('checklist.kilometraje'));
        $combustible = $request->input('combustible', $request->input('checklist.combustible'));
        $condiciones = [];

        if ($kilometraje !== null && $kilometraje !== '') {
            $condiciones['kilometraje'] = $kilometraje;
        }

        if (is_string($combustible) && $combustible !== '') {
            $condiciones['combustible'] = $combustible;
        }

        $cobro = $calculadora->calcular($reserva->id, $devueltoEn, $condiciones);

        return inertia('Devoluciones/Create', [
            'reservation' => $reserva,
            'checklist' => $reserva->checklistEntrega(),
            'estimatedChargesCents' => $cobro['cargos_centavos'],
            'cobro' => $cobro,
            'actaEntrega' => $reserva->actaEntrega,
            'storeUrl' => route('devoluciones.store', $reserva),
            'createUrl' => route('devoluciones.create', $reserva),
            'filters' => [
                'devuelto_at' => $request->string('devuelto_at')->toString(),
                'kilometraje' => is_scalar($kilometraje) ? (string) $kilometraje : '',
                'combustible' => is_string($combustible) ? $combustible : '',
            ],
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
