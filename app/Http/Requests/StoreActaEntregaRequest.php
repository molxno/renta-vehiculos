<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActaEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'entregado_at' => ['required', 'date'],
            'checklist' => ['nullable', 'array'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
