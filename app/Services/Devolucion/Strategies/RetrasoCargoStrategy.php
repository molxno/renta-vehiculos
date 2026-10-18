<?php

declare(strict_types=1);

namespace App\Services\Devolucion\Strategies;

use App\Contracts\CargoDevolucionStrategy;
use App\Models\Reserva;
use DateTimeInterface;
use Illuminate\Support\Carbon;

final class RetrasoCargoStrategy implements CargoDevolucionStrategy
{
    /**
     * @param  array<string, mixed>  $condiciones
     * @return array<int, array{concepto: string, monto_centavos: int}>
     */
    public function lineas(
        Reserva $reserva,
        DateTimeInterface $devueltoEn,
        array $condiciones,
    ): array {
        $devuelto = Carbon::parse($devueltoEn);
        $inicio = Carbon::parse($reserva->inicio);
        $fin = Carbon::parse($reserva->fin);

        if (! $devuelto->gt($fin)) {
            return [];
        }

        $segundos = (int) $fin->diffInSeconds($devuelto);
        $diasRetraso = max(1, (int) ceil($segundos / 86400));
        $diasReserva = max(1, (int) $inicio->diffInDays($fin));
        $tarifaDiaria = intdiv($reserva->total_centavos, $diasReserva);
        $monto = $tarifaDiaria + max(0, $diasRetraso - 1) * 2 * $tarifaDiaria;

        return [[
            'concepto' => 'Retraso de '.$diasRetraso.' día(s)',
            'monto_centavos' => $monto,
        ]];
    }
}
