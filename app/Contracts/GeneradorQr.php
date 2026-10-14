<?php

declare(strict_types=1);

namespace App\Contracts;

interface GeneradorQr
{
    public function generar(int $reservaId): string;
}
