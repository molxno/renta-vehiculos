<?php

declare(strict_types=1);

namespace App\Enums;

enum PagoEstado: string
{
    case Pendiente = 'pendiente';
    case Aprobado = 'aprobado';
    case Rechazado = 'rechazado';
}
