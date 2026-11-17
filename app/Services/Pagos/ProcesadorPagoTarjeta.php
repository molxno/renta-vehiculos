<?php

declare(strict_types=1);

namespace App\Services\Pagos;

use App\Contracts\PasarelaPago;
use App\Contracts\ProcesadorPago;
use App\Models\Reserva;

final class ProcesadorPagoTarjeta implements ProcesadorPago
{
    public function __construct(private PasarelaPago $pasarela) {}

    public function procesar(
        Reserva $reserva,
        int $montoCentavos,
        ?string $numeroTarjeta,
    ): ResultadoPago {
        return ResultadoPago::desdePasarela(
            $this->pasarela->autorizar($reserva->id, $montoCentavos, (string) $numeroTarjeta),
        );
    }
}
