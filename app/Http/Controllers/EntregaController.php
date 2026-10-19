<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Entregas\EvaluarVentanaDeEntrega;
use App\Actions\Entregas\ListarReservasParaEntrega;
use App\Actions\Entregas\RegistrarEntrega;
use App\Enums\NivelCombustible;
use App\Exceptions\EntregaFueraDeVentana;
use App\Http\Requests\FiltrarEntregasRequest;
use App\Http\Requests\StoreActaEntregaRequest;
use App\Http\Requests\VerActaEntregaRequest;
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

    public function create(
        Reserva $reserva,
        EvaluarVentanaDeEntrega $evaluarVentanaDeEntrega,
    ): RedirectResponse|Response {
        $reserva->load(['user.licenciaConduccion', 'vehiculo']);

        if ($reserva->mensajeBloqueoEntrega() === null) {
            try {
                $evaluarVentanaDeEntrega->asegurar($reserva);
            } catch (EntregaFueraDeVentana $excepcion) {
                return $excepcion->render();
            }
        }

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
            'acta' => $this->actaProps($reserva->actaEntrega, $reserva),
            'anticipoCents' => $reserva->anticipoCentavos(),
            'devolucionUrl' => route('devoluciones.create', $reserva),
        ]);
    }

    public function acta(VerActaEntregaRequest $request, Reserva $reserva): Response
    {
        $request->validated();
        $reserva->load(['user.licenciaConduccion', 'vehiculo', 'actaEntrega.coordinador']);

        $acta = $reserva->actaEntrega;

        if (! $acta instanceof ActaEntrega) {
            abort(404);
        }

        $checklist = is_array($acta->checklist) ? $acta->checklist : [];
        $kilometraje = $checklist['kilometraje'] ?? null;
        $combustible = $checklist['combustible'] ?? null;
        $nivel = is_string($combustible) ? NivelCombustible::tryFrom($combustible) : null;
        $licencia = $reserva->user->licenciaConduccion;

        return inertia('Entregas/Acta', [
            'acta' => [
                'numero' => $acta->numero !== null && $acta->numero !== ''
                    ? $acta->numero
                    : sprintf('%s-%d', (string) config('entrega.prefijo_acta'), $acta->id),
                'entregadoAt' => $acta->entregado_at?->toIso8601String(),
                'firmadoAt' => $acta->firmado_at?->toIso8601String(),
                'firmanteNombre' => $acta->firmante_nombre,
                'licenciaResultado' => $acta->licencia_resultado,
                'kilometraje' => is_numeric($kilometraje) ? (int) $kilometraje : null,
                'combustible' => $nivel instanceof NivelCombustible ? $nivel->etiqueta() : (is_string($combustible) ? $combustible : null),
            ],
            'reservation' => [
                'id' => $reserva->id,
                'qrCode' => $reserva->qr_codigo,
            ],
            'cliente' => [
                'nombre' => $reserva->user->name,
                'documento' => $reserva->user->numero_documento,
            ],
            'vehiculo' => [
                'label' => trim($reserva->vehiculo->marca.' '.$reserva->vehiculo->modelo),
                'placa' => $reserva->vehiculo->placa,
            ],
            'licencia' => $this->licenciaProps($licencia),
            'coordinador' => $acta->coordinador?->name,
            'aviso' => 'Documento generado por RENT-GO (demo)',
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
     * @return array{numero: string|null, entregadoAt: string|null, kilometraje: int|null, combustible: string|null, actaUrl: string|null}
     */
    private function actaProps(?ActaEntrega $acta, Reserva $reserva): array
    {
        if (! $acta instanceof ActaEntrega) {
            return [
                'numero' => null,
                'entregadoAt' => null,
                'kilometraje' => null,
                'combustible' => null,
                'actaUrl' => null,
            ];
        }

        $checklist = is_array($acta->checklist) ? $acta->checklist : [];
        $kilometraje = $checklist['kilometraje'] ?? null;
        $combustible = $checklist['combustible'] ?? null;
        $numero = $acta->numero !== null && $acta->numero !== ''
            ? $acta->numero
            : sprintf('%s-%d', (string) config('entrega.prefijo_acta'), $acta->id);

        return [
            'numero' => $numero,
            'entregadoAt' => $acta->entregado_at?->toIso8601String(),
            'kilometraje' => is_numeric($kilometraje) ? (int) $kilometraje : null,
            'combustible' => is_string($combustible) ? $combustible : null,
            'actaUrl' => route('entregas.acta', $reserva),
        ];
    }
}
