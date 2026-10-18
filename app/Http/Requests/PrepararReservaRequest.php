<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UsuarioRol;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class PrepararReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->rol === UsuarioRol::Cliente;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'vehiculo_id' => ['nullable', 'integer', 'exists:vehiculos,id'],
            'inicio' => ['nullable', 'date', 'required_with:fin'],
            'fin' => ['nullable', 'date', 'required_with:inicio', 'after:inicio'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'fin.after' => 'La fecha de devolución debe ser posterior a la fecha de recogida.',
            'fin.required_with' => 'Indica las fechas de recogida y devolución.',
            'inicio.required_with' => 'Indica las fechas de recogida y devolución.',
        ];
    }
}
