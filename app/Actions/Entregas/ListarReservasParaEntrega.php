<?php

declare(strict_types=1);

namespace App\Actions\Entregas;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Builder;

final class ListarReservasParaEntrega
{
    public function __construct(private EvaluarVentanaDeEntrega $evaluarVentanaDeEntrega) {}

    /**
     * @return array<int, array{
     *     id: int,
     *     status: string,
     *     vehicleLabel: string,
     *     vehiclePlate: string,
     *     clientName: string,
     *     clientDocument: string,
     *     qrCode: string|null,
     *     startsAt: string,
     *     endsAt: string,
     *     totalCents: int,
     *     currency: string,
     *     deliverable: bool,
     *     deliveryReason: string|null
     * }>
     */
    public function handle(string $busqueda = ''): array
    {
        $consulta = Reserva::query()
            ->with(['user', 'vehiculo'])
            ->where('estado', ReservaEstado::Confirmada)
            ->orderByDesc('id');

        $termino = trim($busqueda);

        if ($termino !== '') {
            $consulta->where(function (Builder $query) use ($termino): void {
                if (ctype_digit($termino)) {
                    $query->orWhere('id', (int) $termino);
                }

                $query->orWhere('qr_codigo', $termino);

                if (preg_match('/^FAKE-QR-(\d+)$/i', $termino, $coincidencias) === 1) {
                    $query->orWhere('id', (int) $coincidencias[1]);
                }

                $query->orWhereHas('user', function (Builder $usuarios) use ($termino): void {
                    $usuarios->where('numero_documento', $termino);
                });
            });
        }

        return $consulta->get()
            ->map(function (Reserva $reserva): array {
                $ventana = $this->evaluarVentanaDeEntrega->handle($reserva);

                return [
                    'id' => $reserva->id,
                    'status' => $reserva->estado->value,
                    'vehicleLabel' => trim($reserva->vehiculo->marca.' '.$reserva->vehiculo->modelo),
                    'vehiclePlate' => $reserva->vehiculo->placa,
                    'clientName' => $reserva->user->name,
                    'clientDocument' => $reserva->user->numero_documento,
                    'qrCode' => $reserva->qr_codigo,
                    'startsAt' => $reserva->inicio->toIso8601String(),
                    'endsAt' => $reserva->fin->toIso8601String(),
                    'totalCents' => $reserva->total_centavos,
                    'currency' => $reserva->moneda,
                    'deliverable' => $ventana->puedeEntregar,
                    'deliveryReason' => $ventana->motivoListado,
                ];
            })
            ->values()
            ->all();
    }
}
