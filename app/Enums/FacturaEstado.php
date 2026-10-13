<?php

namespace App\Enums;

enum FacturaEstado: string
{
    case Pendiente = 'pendiente';
    case Pagada = 'pagada';
    case Anulada = 'anulada';
}
