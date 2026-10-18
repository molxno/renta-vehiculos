<?php

declare(strict_types=1);

namespace App\States\Reserva;

use App\Enums\ReservaEstado;
use App\Models\Reserva;

final class ConfirmadaState extends AbstractReservaState
{
    public function entregar(Reserva $reserva): void
    {
        $reserva->update(['estado' => ReservaEstado::Entregada]);
    }

    public function cancelar(Reserva $reserva): void
    {
        $reserva->update(['estado' => ReservaEstado::Cancelada]);
    }
}
