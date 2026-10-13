<?php

namespace App\Enums;

enum MetodoPago: string
{
    case Tarjeta = 'tarjeta';
    case Transferencia = 'transferencia';
    case Efectivo = 'efectivo';
}
