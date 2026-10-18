<?php

declare(strict_types=1);

namespace App\States\Reserva;

use App\Enums\ReservaEstado;
use App\Models\Reserva;

final class PendienteState extends AbstractReservaState
{
    public function confirmar(Reserva $reserva): void
    {
        $reserva->update(['estado' => ReservaEstado::Confirmada]);
    }

    public function cancelar(Reserva $reserva): void
    {
        $reserva->update(['estado' => ReservaEstado::Cancelada]);
    }
}
