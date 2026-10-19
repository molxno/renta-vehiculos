<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CalculadoraDevolucion;
use App\Contracts\CargoDevolucionStrategy;
use App\Models\Reserva;
use DateTimeInterface;

final class CalculadoraDevolucionEstandar implements CalculadoraDevolucion
{
    /**
     * @param  iterable<int, CargoDevolucionStrategy>  $estrategias
     */
    public function __construct(private readonly iterable $estrategias) {}

    /**
     * @param  array<string, mixed>  $condiciones
     * @return array{
     *     cargos_centavos: int,
     *     subtotal_centavos: int,
     *     impuestos_centavos: int,
     *     total_centavos: int,
     *     desglose: array<int, array{concepto: string, monto_centavos: int}>
     * }
     */
    public function calcular(
        int $reservaId,
        DateTimeInterface $devueltoEn,
        array $condiciones = [],
    ): array {
        $reserva = Reserva::query()
            ->with(['actaEntrega', 'contrato', 'danos', 'multas'])
            ->findOrFail($reservaId);

        $desglose = [
            ['concepto' => 'Renta base', 'monto_centavos' => $reserva->total_centavos],
        ];
        $cargos = 0;

        foreach ($this->estrategias as $estrategia) {
            foreach ($estrategia->lineas($reserva, $devueltoEn, $condiciones) as $linea) {
                $desglose[] = $linea;
                $cargos += $linea['monto_centavos'];
            }
        }

        foreach ($this->lineasDanosDeCondiciones($condiciones) as $linea) {
            $desglose[] = $linea;
            $cargos += $linea['monto_centavos'];
        }

        $subtotal = $reserva->total_centavos + $cargos;
        $impuestos = intdiv($subtotal * (int) config('devolucion.iva_porcentaje'), 100);

        return [
            'cargos_centavos' => $cargos,
            'subtotal_centavos' => $subtotal,
            'impuestos_centavos' => $impuestos,
            'total_centavos' => $subtotal + $impuestos,
            'desglose' => $desglose,
        ];
    }

    /**
     * Incluye daños aún no persistidos para la vista previa (RNF-011).
     *
     * @param  array<string, mixed>  $condiciones
     * @return array<int, array{concepto: string, monto_centavos: int}>
     */
    private function lineasDanosDeCondiciones(array $condiciones): array
    {
        $danos = $condiciones['danos'] ?? [];

        if (! is_array($danos)) {
            return [];
        }

        $lineas = [];

        foreach ($danos as $dano) {
            if (! is_array($dano)) {
                continue;
            }

            $descripcion = trim((string) ($dano['descripcion'] ?? ''));
            $monto = $dano['monto_centavos'] ?? 0;

            if ($descripcion === '' && (int) $monto === 0) {
                continue;
            }

            $lineas[] = [
                'concepto' => 'Daño: '.$descripcion,
                'monto_centavos' => is_numeric($monto) ? (int) $monto : 0,
            ];
        }

        return $lineas;
    }
}
