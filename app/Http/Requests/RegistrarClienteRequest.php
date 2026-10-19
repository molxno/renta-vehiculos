<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\ReglasLicenciaConduccion;
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
            ...ReglasLicenciaConduccion::rules(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Indica tu nombre completo.',
            'email.required' => 'Indica tu correo electrónico.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'tipo_documento.required' => 'Selecciona el tipo de documento.',
            'tipo_documento.in' => 'El tipo de documento no es válido.',
            'numero_documento.required' => 'Indica el número de documento.',
            'numero_documento.unique' => 'Este número de documento ya está registrado.',
            'telefono.required' => 'Indica tu teléfono.',
            'password.required' => 'Indica una contraseña.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            ...ReglasLicenciaConduccion::messages(),
        ];
    }
}
