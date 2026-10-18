<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UsuarioRol;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class ConfirmarReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();
        $reserva = $this->route('reserva');

        return $usuario instanceof User
            && $usuario->rol === UsuarioRol::Cliente
            && $reserva instanceof Reserva
            && $reserva->user_id === $usuario->id;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [];
    }
}
