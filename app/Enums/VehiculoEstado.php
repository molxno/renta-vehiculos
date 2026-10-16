<?php

declare(strict_types=1);

namespace App\Enums;

enum VehiculoEstado: string
{
    case Disponible = 'disponible';
    case Alquilado = 'alquilado';
    case Contingencia = 'contingencia';
    case Mantenimiento = 'mantenimiento';
}
