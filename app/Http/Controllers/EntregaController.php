<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Entregas\RegistrarEntrega;
use App\Enums\NivelCombustible;
use App\Http\Requests\StoreActaEntregaRequest;
use App\Models\LicenciaConduccion;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class EntregaController extends Controller
{
    public function create(Reserva $reserva): Response
    {
        $reserva->load(['user.licenciaConduccion', 'vehiculo']);

        $licencia = $reserva->user->licenciaConduccion;

        return inertia('Entregas/Create', [
            'reservation' => $reserva,
            'checklist' => $reserva->checklistEntrega(),
            'condicionesContrato' => $reserva->condicionesContrato(),
            'licencia' => $this->licenciaProps($licencia),
            'bloqueo' => $reserva->mensajeBloqueoEntrega(),
            'anticipoCents' => $reserva->anticipoCentavos(),
            'currency' => $reserva->moneda,
            'storeUrl' => route('entregas.store', $reserva),
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
        StoreActaEntregaRequest $request,
        Reserva $reserva,
        RegistrarEntrega $registrarEntrega,
    ): RedirectResponse {
        $registrarEntrega->handle(
            $reserva,
            $request->entregadoAt(),
            $request->checklist(),
            $request->observaciones(),
        );

        return to_route('reservas.show', $reserva);
    }

    /**
     * @return array{numero: string, fecha_vencimiento: string, categoria: string, vigente: bool}|null
     */
    private function licenciaProps(?LicenciaConduccion $licencia): ?array
    {
        if (! $licencia instanceof LicenciaConduccion) {
            return null;
        }

        return [
            'numero' => $licencia->numero,
            'fecha_vencimiento' => $licencia->fecha_vencimiento->toDateString(),
            'categoria' => $licencia->categoria,
            'vigente' => $licencia->estaVigente(),
        ];
    }
}
