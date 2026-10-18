<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Reserva;

interface ReservaState
{
    public function confirmar(Reserva $reserva): void;

    public function entregar(Reserva $reserva): void;

    public function devolver(Reserva $reserva): void;

    public function pagar(Reserva $reserva): void;

    public function cancelar(Reserva $reserva): void;
}
