<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UsuarioRol;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class StoreReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();

        return $usuario instanceof User && $usuario->rol === UsuarioRol::Cliente;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'vehiculo_id' => ['required', 'integer', 'exists:vehiculos,id'],
            'convenio_id' => ['nullable', 'integer', 'exists:convenios,id'],
            'inicio' => ['required', 'date'],
            'fin' => ['required', 'date', 'after:inicio'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'vehiculo_id.required' => 'Selecciona un vehículo.',
            'vehiculo_id.integer' => 'El vehículo seleccionado no es válido.',
            'vehiculo_id.exists' => 'El vehículo seleccionado no existe.',
            'convenio_id.integer' => 'El convenio seleccionado no es válido.',
            'convenio_id.exists' => 'El convenio seleccionado no existe.',
            'inicio.required' => 'Indica la fecha de inicio.',
            'inicio.date' => 'La fecha de inicio no es válida.',
            'fin.required' => 'Indica la fecha de fin.',
            'fin.date' => 'La fecha de fin no es válida.',
            'fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];
    }
}
