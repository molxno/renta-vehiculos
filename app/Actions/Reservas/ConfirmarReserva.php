<?php

declare(strict_types=1);

namespace App\Actions\Reservas;

use App\Contracts\GeneradorQr;
use App\Exceptions\TransicionEstadoInvalida;
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

            try {
                $reserva->confirmar();
            } catch (TransicionEstadoInvalida $excepcion) {
                throw ValidationException::withMessages([
                    'reserva' => $excepcion->getMessage(),
                ]);
            }

            $reserva->update([
                'qr_codigo' => $this->generadorQr->generar($reserva->id),
            ]);

            return $reserva;
        });
    }
}
