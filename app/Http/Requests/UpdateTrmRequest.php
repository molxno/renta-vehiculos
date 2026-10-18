<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UsuarioRol;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateTrmRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();

        return $usuario instanceof User && $usuario->rol === UsuarioRol::Administrador;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'valor' => ['required', 'numeric', 'gt:0', 'decimal:0,4', 'max:99999999.9999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'fecha.required' => 'Indica la fecha de vigencia de la TRM.',
            'fecha.date_format' => 'La fecha de vigencia no es válida.',
            'fecha.before_or_equal' => 'La fecha de vigencia no puede ser futura.',
            'valor.required' => 'Indica el valor de la TRM.',
            'valor.numeric' => 'El valor de la TRM debe ser numérico.',
            'valor.gt' => 'El valor de la TRM debe ser mayor que cero.',
            'valor.decimal' => 'La TRM admite hasta cuatro decimales.',
            'valor.max' => 'El valor de la TRM excede el límite permitido.',
        ];
    }

    public function fecha(): string
    {
        return $this->string('fecha')->toString();
    }

    public function valor(): string
    {
        return $this->string('valor')->toString();
    }
}
