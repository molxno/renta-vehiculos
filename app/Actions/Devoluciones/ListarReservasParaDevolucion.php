<?php

declare(strict_types=1);

namespace App\Actions\Devoluciones;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Builder;

final class ListarReservasParaDevolucion
{
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
     *     currency: string
     * }>
     */
    public function handle(string $busqueda = ''): array
    {
        $consulta = Reserva::query()
            ->with(['user', 'vehiculo'])
            ->where('estado', ReservaEstado::Entregada)
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
            ->map(fn (Reserva $reserva): array => [
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
            ])
            ->values()
            ->all();
    }
}
