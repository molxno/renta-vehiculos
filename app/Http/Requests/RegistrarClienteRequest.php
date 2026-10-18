<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegistrarClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'tipo_documento' => ['required', 'string', Rule::in(['CC', 'CE', 'PAS'])],
            'numero_documento' => ['required', 'string', 'max:50', 'unique:users,numero_documento'],
            'telefono' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ];
    }
}
