<?php

namespace App\Contracts;

interface GeneradorQr
{
    public function generar(int $reservaId): string;
}
