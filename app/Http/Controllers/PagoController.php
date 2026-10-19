<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Pagos\ListarFacturasPendientes;
use App\Actions\Pagos\RegistrarPago;
use App\Enums\FacturaEstado;
use App\Enums\MetodoPago;
use App\Enums\PagoEstado;
use App\Http\Requests\FiltrarPagosRequest;
use App\Http\Requests\StorePagoRequest;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

final class PagoController extends Controller
{
    public function index(
        FiltrarPagosRequest $request,
        ListarFacturasPendientes $listarFacturasPendientes,
    ): Response {
        return inertia('Pagos/Index', [
            'invoices' => $listarFacturasPendientes->handle($request->busqueda()),
            'filters' => ['q' => $request->busqueda()],
        ]);
    }

    public function create(Request $request, Reserva $reserva): Response
    {
        $reserva->load(['factura', 'pagos', 'user', 'vehiculo']);
        $factura = $reserva->factura;
        $ultimo = $reserva->pagos->sortByDesc('id')->first();

        return inertia('Pagos/Create', [
            'reservation' => [
                'id' => $reserva->id,
                'estado' => $reserva->estado->value,
                'moneda' => $reserva->moneda,
            ],
            'cliente' => [
                'nombre' => $reserva->user->name,
                'documento' => $reserva->user->numero_documento,
            ],
            'vehiculo' => [
                'label' => trim($reserva->vehiculo->marca.' '.$reserva->vehiculo->modelo),
                'placa' => $reserva->vehiculo->placa,
            ],
            'qrCodigo' => $reserva->qr_codigo,
            'invoice' => $this->invoiceProps($factura),
            'amountCents' => $factura instanceof Factura ? $factura->total_centavos : 0,
            'currency' => $factura instanceof Factura ? $factura->moneda : $reserva->moneda,
            'methods' => array_map(
                fn (MetodoPago $metodo): array => [
                    'value' => $metodo->value,
                    'label' => $this->etiquetaMetodo($metodo),
                ],
                MetodoPago::cases(),
            ),
            'lastPayment' => $this->paymentProps($ultimo instanceof Pago ? $ultimo : null),
            'rechazoActual' => $this->mensajeRechazoActual($request),
            'storeUrl' => route('pagos.store', $reserva),
        ]);
    }

    public function store(
        StorePagoRequest $request,
        Reserva $reserva,
        RegistrarPago $registrarPago,
    ): RedirectResponse {
        try {
            $registrarPago->handle(
                $reserva,
                $request->enum('metodo', MetodoPago::class),
                $request->integer('monto_centavos'),
                $request->string('numero_tarjeta')->toString() ?: null,
            );
        } catch (ValidationException $excepcion) {
            /** @var list<string> $mensajesTarjeta */
            $mensajesTarjeta = $excepcion->errors()['numero_tarjeta'] ?? [];
            $mensaje = $mensajesTarjeta[0] ?? null;

            if (is_string($mensaje)) {
                $request->session()->flash('pago_rechazado', $mensaje);
            }

            throw $excepcion;
        }

        return to_route('pagos.show', $reserva);
    }

    public function show(Reserva $reserva): RedirectResponse|Response
    {
        $reserva->load(['factura', 'pagos', 'user', 'vehiculo']);
        $factura = $reserva->factura;
        $pagoAprobado = $reserva->pagos
            ->filter(fn (Pago $pago): bool => $pago->estado === PagoEstado::Aprobado)
            ->sortByDesc('id')
            ->first();

        if (! $factura instanceof Factura || $factura->estado !== FacturaEstado::Pagada || ! $pagoAprobado instanceof Pago) {
            return to_route('pagos.create', $reserva);
        }

        return inertia('Pagos/Show', [
            'reservation' => [
                'id' => $reserva->id,
                'status' => $reserva->estado->value,
                'vehicleLabel' => trim($reserva->vehiculo->marca.' '.$reserva->vehiculo->modelo),
                'vehiclePlate' => $reserva->vehiculo->placa,
                'clientName' => $reserva->user->name,
                'qrCode' => $reserva->qr_codigo,
                'currency' => $factura->moneda,
            ],
            'invoice' => $this->invoiceProps($factura),
            'payment' => $this->paymentProps($pagoAprobado),
            'facturaUrl' => route('facturas.show', $factura),
        ]);
    }

    /**
     * @return array{
     *     numero: string,
     *     estado: string,
     *     subtotalCents: int,
     *     taxCents: int,
     *     totalCents: int,
     *     desglose: array<int, array{concepto: string, monto_centavos: int}>
     * }|null
     */
    private function invoiceProps(?Factura $factura): ?array
    {
        if (! $factura instanceof Factura) {
            return null;
        }

        return [
            'numero' => $factura->numero,
            'estado' => $factura->estado->value,
            'subtotalCents' => $factura->subtotal_centavos,
            'taxCents' => $factura->impuestos_centavos,
            'totalCents' => $factura->total_centavos,
            'desglose' => $factura->desglose,
        ];
    }

    /**
     * @return array{estado: string, metodo: string, mensaje: string|null, referencia: string|null, montoCents: int}|null
     */
    private function paymentProps(?Pago $pago): ?array
    {
        if (! $pago instanceof Pago) {
            return null;
        }

        return [
            'estado' => $pago->estado->value,
            'metodo' => $pago->metodo->value,
            'mensaje' => $pago->mensaje,
            'referencia' => $pago->referencia,
            'montoCents' => $pago->monto_centavos,
        ];
    }

    private function mensajeRechazoActual(Request $request): ?string
    {
        $mensaje = $request->session()->get('pago_rechazado');

        return is_string($mensaje) ? $mensaje : null;
    }

    private function etiquetaMetodo(MetodoPago $metodo): string
    {
        return match ($metodo) {
            MetodoPago::Tarjeta => 'Tarjeta',
            MetodoPago::Transferencia => 'Transferencia',
            MetodoPago::Efectivo => 'Efectivo',
        };
    }
}
