<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CalculadoraDevolucion;
use App\Enums\NivelCombustible;
use App\Models\Reserva;
use DateTimeInterface;
use Illuminate\Support\Carbon;

final class CalculadoraDevolucionEstandar implements CalculadoraDevolucion
{
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

        $cargos += $this->agregarRetraso($reserva, $devueltoEn, $desglose);
        $cargos += $this->agregarKilometraje($reserva, $condiciones, $desglose);
        $cargos += $this->agregarCombustible($reserva, $condiciones, $desglose);
        $cargos += $this->agregarDanosYMultas($reserva, $desglose);

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
     * @param  array<int, array{concepto: string, monto_centavos: int}>  $desglose
     */
    private function agregarRetraso(Reserva $reserva, DateTimeInterface $devueltoEn, array &$desglose): int
    {
        $devuelto = Carbon::parse($devueltoEn);
        $inicio = Carbon::parse($reserva->inicio);
        $fin = Carbon::parse($reserva->fin);

        if (! $devuelto->gt($fin)) {
            return 0;
        }

        $segundos = (int) $fin->diffInSeconds($devuelto);
        $diasRetraso = max(1, (int) ceil($segundos / 86400));
        $diasReserva = max(1, (int) $inicio->diffInDays($fin));
        $tarifaDiaria = intdiv($reserva->total_centavos, $diasReserva);
        $monto = $tarifaDiaria + max(0, $diasRetraso - 1) * 2 * $tarifaDiaria;

        $desglose[] = [
            'concepto' => 'Retraso de '.$diasRetraso.' día(s)',
            'monto_centavos' => $monto,
        ];

        return $monto;
    }

    /**
     * @param  array<string, mixed>  $condiciones
     * @param  array<int, array{concepto: string, monto_centavos: int}>  $desglose
     */
    private function agregarKilometraje(Reserva $reserva, array $condiciones, array &$desglose): int
    {
        $kmEntrega = $this->enteroONulo($reserva->checklistEntrega()['kilometraje'] ?? null);
        $kmDevolucion = $this->enteroONulo($condiciones['kilometraje'] ?? null);

        if ($kmEntrega === null || $kmDevolucion === null) {
            return 0;
        }

        $condicionesContrato = $reserva->condicionesContrato();
        $incluido = array_key_exists('kilometraje_incluido', $condicionesContrato)
            ? (int) $condicionesContrato['kilometraje_incluido']
            : (int) config('devolucion.kilometraje_incluido');
        $exceso = max(0, $kmDevolucion - $kmEntrega - $incluido);

        if ($exceso === 0) {
            return 0;
        }

        $monto = $exceso * (int) config('devolucion.kilometro_extra_centavos');
        $desglose[] = [
            'concepto' => 'Exceso de kilometraje ('.$exceso.' km)',
            'monto_centavos' => $monto,
        ];

        return $monto;
    }

    /**
     * @param  array<string, mixed>  $condiciones
     * @param  array<int, array{concepto: string, monto_centavos: int}>  $desglose
     */
    private function agregarCombustible(Reserva $reserva, array $condiciones, array &$desglose): int
    {
        $salida = NivelCombustible::tryFrom((string) ($reserva->checklistEntrega()['combustible'] ?? ''));
        $regreso = NivelCombustible::tryFrom((string) ($condiciones['combustible'] ?? ''));

        if ($salida === null || $regreso === null) {
            return 0;
        }

        $faltantes = max(0, $salida->cuartos() - $regreso->cuartos());

        if ($faltantes === 0) {
            return 0;
        }

        $monto = $faltantes * (int) config('devolucion.cuarto_combustible_centavos');
        $desglose[] = [
            'concepto' => 'Combustible faltante ('.$faltantes.' cuarto(s) de tanque)',
            'monto_centavos' => $monto,
        ];

        return $monto;
    }

    /**
     * @param  array<int, array{concepto: string, monto_centavos: int}>  $desglose
     */
    private function agregarDanosYMultas(Reserva $reserva, array &$desglose): int
    {
        $cargos = 0;

        foreach ($reserva->danos as $dano) {
            $desglose[] = [
                'concepto' => 'Daño: '.$dano->descripcion,
                'monto_centavos' => $dano->monto_centavos,
            ];
            $cargos += $dano->monto_centavos;
        }

        foreach ($reserva->multas as $multa) {
            $desglose[] = [
                'concepto' => 'Multa: '.$multa->concepto,
                'monto_centavos' => $multa->monto_centavos,
            ];
            $cargos += $multa->monto_centavos;
        }

        return $cargos;
    }

    private function enteroONulo(mixed $valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return is_numeric($valor) ? (int) $valor : null;
    }
}
