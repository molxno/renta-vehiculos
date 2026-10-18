<?php

declare(strict_types=1);

namespace App\Fakes;

use App\Contracts\TarifaService;
use App\Models\Convenio;
use App\Models\Tarifa;
use App\Models\Trm;
use App\Models\Vehiculo;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final class FakeTarifaService implements TarifaService
{
    private const int IMPUESTO_PORCENTAJE = 19;

    public function cotizar(
        int $vehiculoId,
        DateTimeInterface $inicio,
        DateTimeInterface $fin,
        ?int $convenioId = null,
        ?string $moneda = null,
    ): array {
        $moneda = strtoupper($moneda ?? 'COP');

        if (! in_array($moneda, ['COP', 'USD'], true)) {
            throw ValidationException::withMessages([
                'moneda' => 'La moneda seleccionada no es válida.',
            ]);
        }

        $duracionSegundos = $fin->getTimestamp() - $inicio->getTimestamp();

        if ($duracionSegundos <= 0) {
            throw ValidationException::withMessages([
                'fin' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            ]);
        }

        $vehiculo = Vehiculo::query()->findOrFail($vehiculoId);
        $tarifa = $this->tarifaVigente($vehiculo, $inicio);

        if ($tarifa === null) {
            throw ValidationException::withMessages([
                'vehiculo_id' => 'No hay una tarifa vigente para el vehículo en la fecha seleccionada.',
            ]);
        }

        $dias = intdiv($duracionSegundos + 86399, 86400);
        $subtotalCentavos = $tarifa->valor_diario_centavos * $dias;
        $descuentoPorcentaje = 0;

        if ($convenioId !== null) {
            $convenio = Convenio::query()
                ->whereKey($convenioId)
                ->where('activo', true)
                ->first();

            if ($convenio === null) {
                throw ValidationException::withMessages([
                    'convenio_id' => 'El convenio seleccionado no está activo.',
                ]);
            }

            if ($convenio->descuento_porcentaje > 100) {
                throw ValidationException::withMessages([
                    'convenio_id' => 'El descuento del convenio no es válido.',
                ]);
            }

            $descuentoPorcentaje = $convenio->descuento_porcentaje;
        }

        $descuentoCentavos = intdiv(($subtotalCentavos * $descuentoPorcentaje) + 50, 100);
        $impuestosCentavos = intdiv(($subtotalCentavos * self::IMPUESTO_PORCENTAJE) + 50, 100);
        $totalCentavos = $subtotalCentavos - $descuentoCentavos + $impuestosCentavos;

        if ($moneda === 'USD') {
            $trm = Trm::query()->vigente()->first();

            if ($trm === null) {
                throw ValidationException::withMessages([
                    'moneda' => 'No hay una TRM vigente para cotizar en USD.',
                ]);
            }

            $trmEscalada = $this->escalarDecimal($trm->valor);

            if ($trmEscalada <= 0) {
                throw ValidationException::withMessages([
                    'moneda' => 'No hay una TRM válida para cotizar en USD.',
                ]);
            }

            $subtotalCentavos = $this->convertirACentavosUsd($subtotalCentavos, $trmEscalada);
            $descuentoCentavos = $this->convertirACentavosUsd($descuentoCentavos, $trmEscalada);
            $impuestosCentavos = $this->convertirACentavosUsd($impuestosCentavos, $trmEscalada);
            $totalCentavos = $subtotalCentavos - $descuentoCentavos + $impuestosCentavos;
        }

        return [
            'subtotal_centavos' => $subtotalCentavos,
            'descuento_centavos' => $descuentoCentavos,
            'impuestos_centavos' => $impuestosCentavos,
            'total_centavos' => $totalCentavos,
            'moneda' => $moneda,
        ];
    }

    private function tarifaVigente(Vehiculo $vehiculo, DateTimeInterface $inicio): ?Tarifa
    {
        $fechaInicio = $inicio->format('Y-m-d');
        $vigencia = static function (Builder $query) use ($fechaInicio): void {
            $query->whereDate('vigente_desde', '<=', $fechaInicio)
                ->where(static function (Builder $query) use ($fechaInicio): void {
                    $query->whereNull('vigente_hasta')
                        ->orWhereDate('vigente_hasta', '>=', $fechaInicio);
                })
                ->orderByDesc('vigente_desde')
                ->orderByDesc('id');
        };

        return Tarifa::query()
            ->where('vehiculo_id', $vehiculo->id)
            ->tap($vigencia)
            ->first()
            ?? Tarifa::query()
                ->whereNull('vehiculo_id')
                ->where('tipo_vehiculo', $vehiculo->tipo)
                ->tap($vigencia)
                ->first();
    }

    private function escalarDecimal(string $valor): int
    {
        [$entero, $decimal] = array_pad(explode('.', $valor, 2), 2, '');

        return ((int) $entero * 10000) + (int) str_pad(substr($decimal, 0, 4), 4, '0');
    }

    private function convertirACentavosUsd(int $centavosCop, int $trmEscalada): int
    {
        $unidadesEnteras = intdiv($centavosCop, $trmEscalada);
        $resto = $centavosCop % $trmEscalada;
        $centavosFraccionarios = intdiv(($resto * 10000) + intdiv($trmEscalada, 2), $trmEscalada);

        return ($unidadesEnteras * 10000) + $centavosFraccionarios;
    }
}
