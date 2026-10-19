<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Devoluciones\ListarReservasParaDevolucion;
use App\Actions\Devoluciones\RegistrarDevolucion;
use App\Contracts\CalculadoraDevolucion;
use App\Enums\NivelCombustible;
use App\Http\Requests\FiltrarDevolucionesRequest;
use App\Http\Requests\StoreActaDevolucionRequest;
use App\Models\ActaDevolucion;
use App\Models\Factura;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Response;

final class DevolucionController extends Controller
{
    public function index(
        FiltrarDevolucionesRequest $request,
        ListarReservasParaDevolucion $listarReservasParaDevolucion,
    ): Response {
        return inertia('Devoluciones/Index', [
            'reservations' => $listarReservasParaDevolucion->handle($request->busqueda()),
            'filters' => ['q' => $request->busqueda()],
        ]);
    }

    public function create(Request $request, Reserva $reserva, CalculadoraDevolucion $calculadora): Response
    {
        $reserva->load(['actaEntrega', 'contrato', 'multas', 'danos', 'user', 'vehiculo']);

        $devueltoEn = $request->filled('devuelto_at')
            ? Date::parse($request->string('devuelto_at')->toString())
            : Date::now();

        $kilometraje = $request->input('kilometraje', $request->input('checklist.kilometraje'));
        $combustible = $request->input('combustible', $request->input('checklist.combustible'));
        $danos = $this->danosPreview($request);
        $condiciones = [];

        if ($kilometraje !== null && $kilometraje !== '') {
            $condiciones['kilometraje'] = $kilometraje;
        }

        if (is_string($combustible) && $combustible !== '') {
            $condiciones['combustible'] = $combustible;
        }

        if ($danos !== []) {
            $condiciones['danos'] = $danos;
        }

        $cobro = $calculadora->calcular($reserva->id, $devueltoEn, $condiciones);

        return inertia('Devoluciones/Create', [
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
            'estimatedChargesCents' => $cobro['cargos_centavos'],
            'cobro' => $cobro,
            'actaEntrega' => $reserva->actaEntrega,
            'storeUrl' => route('devoluciones.store', $reserva),
            'createUrl' => route('devoluciones.create', $reserva),
            'filters' => [
                'devuelto_at' => $request->string('devuelto_at')->toString(),
                'kilometraje' => is_scalar($kilometraje) ? (string) $kilometraje : '',
                'combustible' => is_string($combustible) ? $combustible : '',
                'danos' => $danos,
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

        return to_route('devoluciones.show', $reserva);
    }

    public function show(Reserva $reserva): RedirectResponse|Response
    {
        $reserva->load(['user', 'vehiculo', 'actaDevolucion', 'factura']);

        if (! $reserva->actaDevolucion instanceof ActaDevolucion || ! $reserva->factura instanceof Factura) {
            return to_route('devoluciones.create', $reserva);
        }

        return inertia('Devoluciones/Show', [
            'reservation' => [
                'id' => $reserva->id,
                'status' => $reserva->estado->value,
                'vehicleLabel' => trim($reserva->vehiculo->marca.' '.$reserva->vehiculo->modelo),
                'vehiclePlate' => $reserva->vehiculo->placa,
                'clientName' => $reserva->user->name,
                'qrCode' => $reserva->qr_codigo,
                'currency' => $reserva->moneda,
            ],
            'acta' => $this->actaProps($reserva->actaDevolucion),
            'factura' => [
                'numero' => $reserva->factura->numero,
                'estado' => $reserva->factura->estado->value,
                'subtotalCents' => $reserva->factura->subtotal_centavos,
                'taxCents' => $reserva->factura->impuestos_centavos,
                'totalCents' => $reserva->factura->total_centavos,
                'desglose' => $reserva->factura->desglose,
            ],
            'pagoUrl' => route('pagos.create', $reserva),
        ]);
    }

    /**
     * @return array<int, array{descripcion: string, monto_centavos: int}>
     */
    private function danosPreview(Request $request): array
    {
        $danos = $request->input('danos', []);

        if (! is_array($danos)) {
            return [];
        }

        $normalizados = [];

        foreach ($danos as $dano) {
            if (! is_array($dano)) {
                continue;
            }

            $descripcion = trim((string) ($dano['descripcion'] ?? ''));
            $monto = $dano['monto_centavos'] ?? 0;

            if ($descripcion === '' && (int) $monto === 0) {
                continue;
            }

            $normalizados[] = [
                'descripcion' => $descripcion,
                'monto_centavos' => is_numeric($monto) ? (int) $monto : 0,
            ];
        }

        return $normalizados;
    }

    /**
     * @return array{devueltoAt: string|null, kilometraje: int|null, combustible: string|null, observaciones: string|null}
     */
    private function actaProps(ActaDevolucion $acta): array
    {
        $checklist = is_array($acta->checklist) ? $acta->checklist : [];
        $kilometraje = $checklist['kilometraje'] ?? null;
        $combustible = $checklist['combustible'] ?? null;

        return [
            'devueltoAt' => $acta->devuelto_at?->toIso8601String(),
            'kilometraje' => is_numeric($kilometraje) ? (int) $kilometraje : null,
            'combustible' => is_string($combustible) ? $combustible : null,
            'observaciones' => $acta->observaciones,
        ];
    }
}
