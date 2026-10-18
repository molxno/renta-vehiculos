<?php

declare(strict_types=1);

namespace App\Services\Devolucion\Strategies;

use App\Contracts\CargoDevolucionStrategy;
use App\Enums\NivelCombustible;
use App\Models\Reserva;
use DateTimeInterface;

final class CombustibleCargoStrategy implements CargoDevolucionStrategy
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
        $salida = NivelCombustible::tryFrom((string) ($reserva->checklistEntrega()['combustible'] ?? ''));
        $regreso = NivelCombustible::tryFrom((string) ($condiciones['combustible'] ?? ''));

        if ($salida === null || $regreso === null) {
            return [];
        }

        $faltantes = max(0, $salida->cuartos() - $regreso->cuartos());

        if ($faltantes === 0) {
            return [];
        }

        return [[
            'concepto' => 'Combustible faltante ('.$faltantes.' cuarto(s) de tanque)',
            'monto_centavos' => $faltantes * (int) config('devolucion.cuarto_combustible_centavos'),
        ]];
    }
}
