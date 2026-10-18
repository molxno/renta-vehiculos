<?php

declare(strict_types=1);

namespace App\States\Reserva;

use App\Enums\ReservaEstado;
use App\Models\Reserva;

final class FacturadaState extends AbstractReservaState
{
    public function pagar(Reserva $reserva): void
    {
        $reserva->update(['estado' => ReservaEstado::Pagada]);
    }
}
