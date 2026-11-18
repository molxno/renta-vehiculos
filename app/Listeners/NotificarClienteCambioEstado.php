<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ReservaEstado;
use App\Events\ReservaEstadoCambiado;
use App\Mail\ReservaEstadoCambiadoMail;
use Illuminate\Support\Facades\Mail;

final class NotificarClienteCambioEstado
{
    private const ESTADOS_CON_CORREO = [
        ReservaEstado::Confirmada,
        ReservaEstado::Entregada,
        ReservaEstado::Facturada,
        ReservaEstado::Pagada,
    ];

    public function handle(ReservaEstadoCambiado $evento): void
    {
        if (! in_array($evento->estadoNuevo, self::ESTADOS_CON_CORREO, true)) {
            return;
        }

        $evento->reserva->loadMissing('user');

        Mail::to($evento->reserva->user->email)
            ->send(new ReservaEstadoCambiadoMail($evento));
    }
}
