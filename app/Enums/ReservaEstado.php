<?php

declare(strict_types=1);

namespace App\Enums;

enum ReservaEstado: string
{
    case Pendiente = 'pendiente';
    case Confirmada = 'confirmada';
    case Entregada = 'entregada';
    case Devuelta = 'devuelta';
    case Facturada = 'facturada';
    case Pagada = 'pagada';
    case Cancelada = 'cancelada';
}
