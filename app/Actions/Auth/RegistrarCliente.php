<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\UsuarioRol;
use App\Models\User;

final class RegistrarCliente
{
    public function handle(
        string $name,
        string $email,
        string $tipoDocumento,
        string $numeroDocumento,
        string $telefono,
        string $password,
    ): User {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $numeroDocumento,
            'telefono' => $telefono,
            'password' => $password,
            'rol' => UsuarioRol::Cliente,
        ]);
    }
}
