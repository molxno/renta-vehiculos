<?php

namespace App\Enums;

enum UsuarioRol: string
{
    case Cliente = 'cliente';
    case Coordinador = 'coordinador';
    case Cajero = 'cajero';
    case Administrador = 'administrador';
    case Gerente = 'gerente';
}
