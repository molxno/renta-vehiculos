<?php

namespace App\Fakes;

use App\Contracts\GeneradorQr;

class FakeGeneradorQr implements GeneradorQr
{
    public function generar(int $reservaId): string
    {
        return 'FAKE-QR-'.$reservaId;
    }
}
