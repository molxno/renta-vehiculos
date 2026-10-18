<?php

declare(strict_types=1);

namespace App\Actions\Reservas;

use App\Contracts\GeneradorQr;
use App\Enums\ReservaEstado;
use App\Models\Reserva;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ConfirmarReserva
{
    public function __construct(private GeneradorQr $generadorQr) {}

    public function handle(Reserva $reserva): Reserva
    {
        return DB::transaction(function () use ($reserva): Reserva {
            $reserva = Reserva::query()
                ->whereKey($reserva->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($reserva->estado !== ReservaEstado::Pendiente) {
                throw ValidationException::withMessages([
                    'reserva' => 'Solo se pueden confirmar reservas pendientes.',
                ]);
            }

            $reserva->update([
                'estado' => ReservaEstado::Confirmada,
                'qr_codigo' => $this->generadorQr->generar($reserva->id),
            ]);

            return $reserva;
        });
    }
}
