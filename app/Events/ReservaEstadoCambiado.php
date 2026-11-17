<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Events\Dispatchable;

final class ReservaEstadoCambiado
{
    use Dispatchable;

    public function __construct(
        public Reserva $reserva,
        public ReservaEstado $estadoAnterior,
        public ReservaEstado $estadoNuevo,
        public CarbonInterface $fecha,
    ) {}
}
