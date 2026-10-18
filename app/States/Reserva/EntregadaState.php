<?php

declare(strict_types=1);

namespace App\States\Reserva;

use App\Enums\ReservaEstado;
use App\Models\Reserva;

final class EntregadaState extends AbstractReservaState
{
    public function devolver(Reserva $reserva): void
    {
        $reserva->update(['estado' => ReservaEstado::Facturada]);
    }
}
