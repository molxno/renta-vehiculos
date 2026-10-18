<?php

declare(strict_types=1);

namespace App\Enums;

enum FacturaEstado: string
{
    case Pendiente = 'pendiente';
    case Pagada = 'pagada';
    case Anulada = 'anulada';
}
