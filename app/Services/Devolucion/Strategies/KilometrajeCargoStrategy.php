<?php

declare(strict_types=1);

namespace App\Services\Devolucion\Strategies;

use App\Contracts\CargoDevolucionStrategy;
use App\Models\Reserva;
use DateTimeInterface;

final class KilometrajeCargoStrategy implements CargoDevolucionStrategy
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
        $kmEntrega = $this->enteroONulo($reserva->checklistEntrega()['kilometraje'] ?? null);
        $kmDevolucion = $this->enteroONulo($condiciones['kilometraje'] ?? null);

        if ($kmEntrega === null || $kmDevolucion === null) {
            return [];
        }

        $condicionesContrato = $reserva->condicionesContrato();
        $incluido = array_key_exists('kilometraje_incluido', $condicionesContrato)
            ? (int) $condicionesContrato['kilometraje_incluido']
            : (int) config('devolucion.kilometraje_incluido');
        $exceso = max(0, $kmDevolucion - $kmEntrega - $incluido);

        if ($exceso === 0) {
            return [];
        }

        return [[
            'concepto' => 'Exceso de kilometraje ('.$exceso.' km)',
            'monto_centavos' => $exceso * (int) config('devolucion.kilometro_extra_centavos'),
        ]];
    }

    private function enteroONulo(mixed $valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return is_numeric($valor) ? (int) $valor : null;
    }
}
