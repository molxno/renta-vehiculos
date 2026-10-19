<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Catalogo\ListarCatalogoVehiculos;
use App\Actions\Reservas\ConfirmarReserva as ConfirmarReservaAction;
use App\Actions\Reservas\CrearReserva;
use App\Actions\Reservas\ListarReservas;
use App\Contracts\TarifaService;
use App\Enums\FacturaEstado;
use App\Enums\PagoEstado;
use App\Enums\ReservaEstado;
use App\Enums\UsuarioRol;
use App\Exceptions\TransicionEstadoInvalida;
use App\Http\Requests\ConfirmarReservaRequest;
use App\Http\Requests\PrepararReservaRequest;
use App\Http\Requests\StoreReservaRequest;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\Trm;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

final class ReservaController extends Controller
{
    public function index(Request $request, ListarReservas $listarReservas): Response
    {
        $usuario = $request->user();

        if (! $usuario instanceof User) {
            abort(403);
        }

        return inertia('Reservas/Index', [
            'reservations' => $listarReservas->handle($usuario),
            'filters' => ['page' => 1],
            'avisoLicencia' => $this->avisoLicenciaPara($usuario),
            'cuentaUrl' => route('cuenta.show'),
        ]);
    }

    public function create(
        PrepararReservaRequest $request,
        ListarCatalogoVehiculos $listarCatalogoVehiculos,
        TarifaService $tarifa,
    ): Response {
        $inicio = $request->string('inicio')->toString();
        $fin = $request->string('fin')->toString();
        $vehiculoId = $request->integer('vehiculo_id');

        $catalogo = $listarCatalogoVehiculos->handle([
            'startDate' => $inicio,
            'endDate' => $fin,
        ]);
        $vehicles = array_values(array_filter(
            $catalogo['vehicles'],
            fn (array $vehicle): bool => $vehicle['status'] === 'disponible',
        ));
        $selectedVehicle = null;

        foreach ($vehicles as $vehicle) {
            if ($vehicle['id'] === $vehiculoId) {
                $selectedVehicle = $vehicle;

                break;
            }
        }

        $trm = Trm::query()->vigente()->first();
        $quote = null;

        if ($selectedVehicle !== null && $inicio !== '' && $fin !== '') {
            $cotizacion = $tarifa->cotizar(
                $vehiculoId,
                Date::parse($inicio)->startOfDay(),
                Date::parse($fin)->startOfDay(),
            );
            $quote = [
                'dailyRateCents' => $selectedVehicle['dailyRateCents'],
                'subtotalCents' => $cotizacion['subtotal_centavos'],
                'discountCents' => $cotizacion['descuento_centavos'],
                'taxesCents' => $cotizacion['impuestos_centavos'],
                'totalCents' => $cotizacion['total_centavos'],
                'currency' => $cotizacion['moneda'],
            ];
        }

        return inertia('Reservas/Create', [
            'vehicles' => $vehicles,
            'filters' => [
                'vehiculoId' => $vehiculoId > 0 ? $vehiculoId : null,
                'inicio' => $inicio,
                'fin' => $fin,
            ],
            'trm' => $trm instanceof Trm ? (string) $trm->valor : null,
            'quote' => $quote,
            'rentalNotes' => [
                'La licencia de conducción vigente se verifica al entregar el vehículo.',
                'Combustible: devolver al mismo nivel; hay cargo si falta.',
                'Entrega y devolución en la sede elegida (informativo).',
            ],
            'rentalDisclaimer' => 'Sin cargos al reservar. El cobro se hace al devolver el vehículo. Cancelar una reserva no forma parte de este MVP.',
        ]);
    }

    public function store(StoreReservaRequest $request, CrearReserva $crearReserva): RedirectResponse
    {
        $reserva = $crearReserva->handle(
            $request->cliente(),
            $request->integer('vehiculo_id'),
            $request->convenioId(),
            $request->inicio(),
            $request->fin(),
            $request->moneda(),
        );

        return to_route('reservas.show', $reserva);
    }

    public function show(Request $request, Reserva $reserva): Response
    {
        $reserva->loadMissing(['vehiculo', 'actaEntrega', 'factura', 'pagos']);
        $usuario = $request->user();

        return inertia('Reservas/Show', [
            'reservation' => [
                'id' => $reserva->id,
                'status' => $reserva->estado->value,
                'vehicleId' => $reserva->vehiculo_id,
                'vehicleLabel' => trim($reserva->vehiculo->marca.' '.$reserva->vehiculo->modelo),
                'vehiclePlate' => $reserva->vehiculo->placa,
                'startsAt' => $reserva->inicio->toIso8601String(),
                'endsAt' => $reserva->fin->toIso8601String(),
                'totalCents' => $reserva->total_centavos,
                'currency' => $reserva->moneda,
                'qrCode' => $reserva->qr_codigo,
            ],
            'timeline' => $this->lineaDeTiempo($reserva),
            'actaUrl' => $reserva->actaEntrega ? route('entregas.acta', $reserva) : null,
            'avisoLicencia' => $usuario instanceof User && $usuario->id === $reserva->user_id
                ? $this->avisoLicenciaPara($usuario)
                : null,
            'cuentaUrl' => route('cuenta.show'),
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
        try {
            $reserva->cancelar();
        } catch (TransicionEstadoInvalida $excepcion) {
            throw ValidationException::withMessages([
                'reserva' => $excepcion->getMessage(),
            ]);
        }

        return to_route('reservas.show', $reserva);
    }

    private function avisoLicenciaPara(User $usuario): ?string
    {
        if ($usuario->rol !== UsuarioRol::Cliente || ! $usuario->necesitaRegistrarLicencia()) {
            return null;
        }

        return 'Registra tu licencia para poder recibir tu vehículo';
    }

    /**
     * @return list<array{key: string, label: string, at: string|null, done: bool}>
     */
    private function lineaDeTiempo(Reserva $reserva): array
    {
        $pagoAprobado = $reserva->pagos->first(
            fn (Pago $pago): bool => $pago->estado === PagoEstado::Aprobado,
        );

        $confirmada = ! in_array($reserva->estado, [ReservaEstado::Pendiente, ReservaEstado::Cancelada], true);
        $entregada = $reserva->actaEntrega !== null
            || in_array($reserva->estado, [
                ReservaEstado::Entregada,
                ReservaEstado::Devuelta,
                ReservaEstado::Facturada,
                ReservaEstado::Pagada,
            ], true);
        $facturada = $reserva->factura !== null
            || in_array($reserva->estado, [ReservaEstado::Facturada, ReservaEstado::Pagada], true);
        $pagada = $reserva->estado === ReservaEstado::Pagada
            || $reserva->factura?->estado === FacturaEstado::Pagada
            || $pagoAprobado instanceof Pago;

        return [
            [
                'key' => 'confirmada',
                'label' => 'Confirmada',
                'at' => $confirmada ? $reserva->created_at?->toIso8601String() : null,
                'done' => $confirmada,
            ],
            [
                'key' => 'entregada',
                'label' => 'Entregada',
                'at' => $reserva->actaEntrega?->entregado_at?->toIso8601String(),
                'done' => $entregada,
            ],
            [
                'key' => 'facturada',
                'label' => 'Facturada',
                'at' => $reserva->factura?->created_at?->toIso8601String(),
                'done' => $facturada,
            ],
            [
                'key' => 'pagada',
                'label' => 'Pagada',
                'at' => $pagoAprobado?->created_at?->toIso8601String()
                    ?? ($pagada ? $reserva->factura?->updated_at?->toIso8601String() : null),
                'done' => $pagada,
            ],
        ];
    }
}
