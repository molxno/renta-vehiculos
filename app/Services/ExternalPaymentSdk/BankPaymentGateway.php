<?php

declare(strict_types=1);

namespace App\Services\ExternalPaymentSdk;

final class BankPaymentGateway
{
    /**
     * Firma del SDK bancario simulado. El monto llega en unidades de moneda,
     * no en centavos, y la respuesta no coincide con PasarelaPago.
     *
     * @return array{success: bool, transaction_id: string|null, message: string, response_code: string}
     */
    public function processTransaction(float $amount, string $cardNumber): array
    {
        $aprobado = $amount > 0 && ! str_ends_with($cardNumber, '0000');

        return [
            'success' => $aprobado,
            'transaction_id' => $aprobado ? 'BNK-'.substr(hash('xxh128', $cardNumber.(string) $amount), 0, 12) : null,
            'message' => $aprobado ? 'Transaction approved' : 'Transaction declined',
            'response_code' => $aprobado ? '00' : '05',
        ];
    }
}
