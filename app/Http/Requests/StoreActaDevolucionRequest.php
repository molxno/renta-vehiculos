<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreActaDevolucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'devuelto_at' => ['required', 'date'],
            'checklist' => ['nullable', 'array'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
