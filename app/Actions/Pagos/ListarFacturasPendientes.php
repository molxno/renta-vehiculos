<?php

declare(strict_types=1);

namespace App\Actions\Pagos;

use App\Enums\FacturaEstado;
use App\Enums\ReservaEstado;
use App\Models\Factura;
use Illuminate\Database\Eloquent\Builder;

final class ListarFacturasPendientes
{
    /**
     * @return array<int, array{
     *     reservationId: int,
     *     invoiceNumber: string,
     *     status: string,
     *     vehicleLabel: string,
     *     vehiclePlate: string,
     *     clientName: string,
     *     clientDocument: string,
     *     qrCode: string|null,
     *     totalCents: int,
     *     currency: string
     * }>
     */
    public function handle(string $busqueda = ''): array
    {
        $consulta = Factura::query()
            ->with(['reserva.user', 'reserva.vehiculo'])
            ->where('estado', FacturaEstado::Pendiente)
            ->whereHas('reserva', function (Builder $reservas): void {
                $reservas->where('estado', ReservaEstado::Facturada);
            })
            ->orderByDesc('id');

        $termino = trim($busqueda);

        if ($termino !== '') {
            $consulta->where(function (Builder $query) use ($termino): void {
                $query->where('numero', $termino);

                if (ctype_digit($termino)) {
                    $query->orWhere('reserva_id', (int) $termino);
                }

                $query->orWhereHas('reserva', function (Builder $reservas) use ($termino): void {
                    $reservas->where('qr_codigo', $termino);
                });

                if (preg_match('/^FAKE-QR-(\d+)$/i', $termino, $coincidencias) === 1) {
                    $query->orWhere('reserva_id', (int) $coincidencias[1]);
                }
            });
        }

        return $consulta->get()
            ->map(function (Factura $factura): array {
                $reserva = $factura->reserva;

                return [
                    'reservationId' => $reserva->id,
                    'invoiceNumber' => $factura->numero,
                    'status' => $factura->estado->value,
                    'vehicleLabel' => trim($reserva->vehiculo->marca.' '.$reserva->vehiculo->modelo),
                    'vehiclePlate' => $reserva->vehiculo->placa,
                    'clientName' => $reserva->user->name,
                    'clientDocument' => $reserva->user->numero_documento,
                    'qrCode' => $reserva->qr_codigo,
                    'totalCents' => $factura->total_centavos,
                    'currency' => $factura->moneda,
                ];
            })
            ->values()
            ->all();
    }
}
