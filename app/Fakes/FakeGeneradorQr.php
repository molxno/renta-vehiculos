<?php

declare(strict_types=1);

namespace App\Fakes;

use App\Contracts\GeneradorQr;

final class FakeGeneradorQr implements GeneradorQr
{
    public function generar(int $reservaId): string
    {
        return 'FAKE-QR-'.$reservaId;
    }
}
