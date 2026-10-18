<?php

declare(strict_types=1);

namespace App\Actions\Reservas;

use App\Enums\UsuarioRol;
use App\Models\Reserva;
use App\Models\User;

final class ListarReservas
{
    /**
     * @return array<int, array{
     *     id: int,
     *     status: string,
     *     vehicleLabel: string,
     *     vehiclePlate: string,
     *     startsAt: string,
     *     endsAt: string,
     *     totalCents: int,
     *     currency: string
     * }>
     */
    public function handle(User $usuario): array
    {
        $consulta = Reserva::query()
            ->with('vehiculo')
            ->orderByDesc('id');

        if ($usuario->rol === UsuarioRol::Cliente) {
            $consulta->where('user_id', $usuario->id);
        }

        return $consulta->get()
            ->map(fn (Reserva $reserva): array => [
                'id' => $reserva->id,
                'status' => $reserva->estado->value,
                'vehicleLabel' => trim($reserva->vehiculo->marca.' '.$reserva->vehiculo->modelo),
                'vehiclePlate' => $reserva->vehiculo->placa,
                'startsAt' => $reserva->inicio->toIso8601String(),
                'endsAt' => $reserva->fin->toIso8601String(),
                'totalCents' => $reserva->total_centavos,
                'currency' => $reserva->moneda,
            ])
            ->all();
    }
}
