<?php

declare(strict_types=1);

namespace App\Enums;

enum NivelCombustible: string
{
    case Vacio = 'vacio';
    case Cuarto = 'cuarto';
    case Medio = 'medio';
    case TresCuartos = 'tres_cuartos';
    case Lleno = 'lleno';

    public function cuartos(): int
    {
        return match ($this) {
            self::Vacio => 0,
            self::Cuarto => 1,
            self::Medio => 2,
            self::TresCuartos => 3,
            self::Lleno => 4,
        };
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Vacio => 'Vacío',
            self::Cuarto => 'Un cuarto',
            self::Medio => 'Medio',
            self::TresCuartos => 'Tres cuartos',
            self::Lleno => 'Lleno',
        };
    }
}
