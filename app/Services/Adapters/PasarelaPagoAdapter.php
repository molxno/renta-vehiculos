<?php

declare(strict_types=1);

namespace App\Services\Adapters;

use App\Contracts\PasarelaPago;
use App\Services\ExternalPaymentSdk\BankPaymentGateway;

final class PasarelaPagoAdapter implements PasarelaPago
{
    public function __construct(private BankPaymentGateway $pasarela) {}

    /**
     * @return array{
     *     aprobado: bool,
     *     referencia: string|null,
     *     mensaje: string,
     *     codigo: string
     * }
     */
    public function autorizar(
        int $reservaId,
        int $montoCentavos,
        string $numeroTarjeta,
    ): array {
        $resultado = $this->pasarela->processTransaction($montoCentavos / 100, $numeroTarjeta);
        $aprobado = $resultado['success'];

        return [
            'aprobado' => $aprobado,
            'referencia' => $aprobado ? 'FAKE-PAY-'.$reservaId : null,
            'mensaje' => $aprobado ? 'Pago autorizado' : 'Pago rechazado',
            'codigo' => $resultado['response_code'],
        ];
    }
}
