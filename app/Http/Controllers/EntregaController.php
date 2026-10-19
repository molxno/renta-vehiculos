<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Entregas\ListarReservasParaEntrega;
use App\Actions\Entregas\RegistrarEntrega;
use App\Enums\NivelCombustible;
use App\Http\Requests\FiltrarEntregasRequest;
use App\Http\Requests\StoreActaEntregaRequest;
use App\Models\ActaEntrega;
use App\Models\LicenciaConduccion;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class EntregaController extends Controller
{
    public function index(
        FiltrarEntregasRequest $request,
        ListarReservasParaEntrega $listarReservasParaEntrega,
    ): Response {
        return inertia('Entregas/Index', [
            'reservations' => $listarReservasParaEntrega->handle($request->busqueda()),
            'filters' => ['q' => $request->busqueda()],
        ]);
    }

    public function create(Reserva $reserva): Response
    {
        $reserva->load(['user.licenciaConduccion', 'vehiculo']);

        $licencia = $reserva->user->licenciaConduccion;

        return inertia('Entregas/Create', [
            'reservation' => $reserva,
            'cliente' => [
                'nombre' => $reserva->user->name,
                'documento' => $reserva->user->numero_documento,
            ],
            'vehiculo' => [
                'label' => trim($reserva->vehiculo->marca.' '.$reserva->vehiculo->modelo),
                'placa' => $reserva->vehiculo->placa,
            ],
            'qrCodigo' => $reserva->qr_codigo,
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

        return to_route('entregas.show', $reserva);
    }

    public function show(Reserva $reserva): RedirectResponse|Response
    {
        if (! $reserva->estaEntregada()) {
            return to_route('entregas.create', $reserva);
        }

        $reserva->load(['user', 'vehiculo', 'actaEntrega']);

        return inertia('Entregas/Show', [
            'reservation' => [
                'id' => $reserva->id,
                'status' => $reserva->estado->value,
                'vehicleLabel' => trim($reserva->vehiculo->marca.' '.$reserva->vehiculo->modelo),
                'vehiclePlate' => $reserva->vehiculo->placa,
                'clientName' => $reserva->user->name,
                'qrCode' => $reserva->qr_codigo,
                'currency' => $reserva->moneda,
            ],
            'acta' => $this->actaProps($reserva->actaEntrega),
            'anticipoCents' => $reserva->anticipoCentavos(),
            'devolucionUrl' => route('devoluciones.create', $reserva),
        ]);
    }

    /**
     * @return array{numero: string, fecha_vencimiento: string, categoria: string, vigente: bool, categoria_valida: bool}|null
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
            'categoria_valida' => $licencia->categoriaValida(),
        ];
    }

    /**
     * @return array{entregadoAt: string|null, kilometraje: int|null, combustible: string|null}
     */
    private function actaProps(?ActaEntrega $acta): array
    {
        $checklist = is_array($acta?->checklist) ? $acta->checklist : [];
        $kilometraje = $checklist['kilometraje'] ?? null;
        $combustible = $checklist['combustible'] ?? null;

        return [
            'entregadoAt' => $acta?->entregado_at?->toIso8601String(),
            'kilometraje' => is_numeric($kilometraje) ? (int) $kilometraje : null,
            'combustible' => is_string($combustible) ? $combustible : null,
        ];
    }
}
