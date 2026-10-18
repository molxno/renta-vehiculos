<?php

declare(strict_types=1);

namespace App\Enums;

enum MetodoPago: string
{
    case Tarjeta = 'tarjeta';
    case Transferencia = 'transferencia';
    case Efectivo = 'efectivo';
}
