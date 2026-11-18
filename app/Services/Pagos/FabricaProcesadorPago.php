<?php

declare(strict_types=1);

namespace App\Services\Pagos;

use App\Contracts\PasarelaPago;
use App\Contracts\ProcesadorPago;
use App\Enums\MetodoPago;

final class FabricaProcesadorPago
{
    public function __construct(private PasarelaPago $pasarela) {}

    public function crear(MetodoPago $metodo): ProcesadorPago
    {
        return match ($metodo) {
            MetodoPago::Tarjeta => new ProcesadorPagoTarjeta($this->pasarela),
            MetodoPago::Efectivo => new ProcesadorPagoEfectivo,
            MetodoPago::Transferencia => new ProcesadorPagoTransferencia,
        };
    }
}
