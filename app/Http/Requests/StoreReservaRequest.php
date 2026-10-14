<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'vehiculo_id' => ['required', 'integer', 'exists:vehiculos,id'],
            'convenio_id' => ['nullable', 'integer', 'exists:convenios,id'],
            'inicio' => ['required', 'date'],
            'fin' => ['required', 'date', 'after:inicio'],
            'moneda' => ['nullable', 'string', 'size:3'],
        ];
    }
}
