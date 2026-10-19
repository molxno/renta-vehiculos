<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Cuenta\GuardarLicenciaCliente;
use App\Enums\UsuarioRol;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RegistrarCliente
{
    public function __construct(private GuardarLicenciaCliente $guardarLicenciaCliente) {}

    public function handle(
        string $name,
        string $email,
        string $tipoDocumento,
        string $numeroDocumento,
        string $telefono,
        string $password,
        string $licenciaNumero,
        string $licenciaCategoria,
        string $licenciaVencimiento,
    ): User {
        return DB::transaction(function () use (
            $name,
            $email,
            $tipoDocumento,
            $numeroDocumento,
            $telefono,
            $password,
            $licenciaNumero,
            $licenciaCategoria,
            $licenciaVencimiento,
        ): User {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'tipo_documento' => $tipoDocumento,
                'numero_documento' => $numeroDocumento,
                'telefono' => $telefono,
                'password' => $password,
                'rol' => UsuarioRol::Cliente,
            ]);

            $this->guardarLicenciaCliente->handle(
                $user,
                $licenciaNumero,
                $licenciaCategoria,
                $licenciaVencimiento,
            );

            return $user;
        });
    }
}
