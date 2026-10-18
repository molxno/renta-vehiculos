<?php

declare(strict_types=1);

namespace App\Services;

final class ConvertidorTrm
{
    public function escalarDecimal(string $valor): int
    {
        [$entero, $decimal] = array_pad(explode('.', $valor, 2), 2, '');

        return ((int) $entero * 10000) + (int) str_pad(substr($decimal, 0, 4), 4, '0');
    }

    public function convertirACentavosUsd(int $centavosCop, int $trmEscalada): int
    {
        $unidadesEnteras = intdiv($centavosCop, $trmEscalada);
        $resto = $centavosCop % $trmEscalada;
        $centavosFraccionarios = intdiv(($resto * 10000) + intdiv($trmEscalada, 2), $trmEscalada);

        return ($unidadesEnteras * 10000) + $centavosFraccionarios;
    }
}
