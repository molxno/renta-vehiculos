<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UsuarioRol;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class VerActaEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();
        $reserva = $this->route('reserva');

        if (! $usuario instanceof User || ! $reserva instanceof Reserva) {
            return false;
        }

        if (in_array($usuario->rol, [UsuarioRol::Coordinador, UsuarioRol::Administrador], true)) {
            return true;
        }

        return $usuario->rol === UsuarioRol::Cliente
            && $usuario->id === $reserva->user_id;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
