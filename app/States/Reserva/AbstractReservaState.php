<?php

declare(strict_types=1);

namespace App\States\Reserva;

use App\Contracts\ReservaState;
use App\Exceptions\TransicionEstadoInvalida;
use App\Models\Reserva;

abstract class AbstractReservaState implements ReservaState
{
    public function confirmar(Reserva $reserva): void
    {
        throw TransicionEstadoInvalida::para('confirmar', $reserva->estado);
    }

    public function entregar(Reserva $reserva): void
    {
        throw TransicionEstadoInvalida::para('entregar', $reserva->estado);
    }

    public function devolver(Reserva $reserva): void
    {
        throw TransicionEstadoInvalida::para('devolver', $reserva->estado);
    }

    public function pagar(Reserva $reserva): void
    {
        throw TransicionEstadoInvalida::para('pagar', $reserva->estado);
    }

    public function cancelar(Reserva $reserva): void
    {
        throw TransicionEstadoInvalida::para('cancelar', $reserva->estado);
    }
}
