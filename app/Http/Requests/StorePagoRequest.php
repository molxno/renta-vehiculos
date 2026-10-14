<?php

namespace App\Http\Requests;

use App\Enums\MetodoPago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'metodo' => ['required', Rule::enum(MetodoPago::class)],
            'monto_centavos' => ['required', 'integer', 'min:1'],
            'numero_tarjeta' => ['required_if:metodo,tarjeta', 'nullable', 'string', 'digits_between:13,19'],
        ];
    }
}
