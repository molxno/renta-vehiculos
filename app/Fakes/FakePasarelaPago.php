<?php

namespace App\Fakes;

use App\Contracts\PasarelaPago;

class FakePasarelaPago implements PasarelaPago
{
    public function autorizar(
        int $reservaId,
        int $montoCentavos,
        string $numeroTarjeta,
    ): array {
        $aprobado = ! str_ends_with($numeroTarjeta, '0000');

        return [
            'aprobado' => $aprobado,
            'referencia' => $aprobado ? 'FAKE-PAY-'.$reservaId : null,
            'mensaje' => $aprobado ? 'Pago autorizado' : 'Pago rechazado',
            'codigo' => $aprobado ? '00' : '05',
        ];
    }
}
