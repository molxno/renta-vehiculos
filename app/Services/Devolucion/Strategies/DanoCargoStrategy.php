<?php

declare(strict_types=1);

namespace App\Services\Devolucion\Strategies;

use App\Contracts\CargoDevolucionStrategy;
use App\Models\Reserva;
use DateTimeInterface;

final class DanoCargoStrategy implements CargoDevolucionStrategy
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
        $lineas = [];

        foreach ($reserva->danos as $dano) {
            $lineas[] = [
                'concepto' => 'Daño: '.$dano->descripcion,
                'monto_centavos' => $dano->monto_centavos,
            ];
        }

        foreach ($reserva->multas as $multa) {
            $lineas[] = [
                'concepto' => 'Multa: '.$multa->concepto,
                'monto_centavos' => $multa->monto_centavos,
            ];
        }

        return $lineas;
    }
}
