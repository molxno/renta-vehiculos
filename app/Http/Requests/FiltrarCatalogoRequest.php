<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class FiltrarCatalogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:50'],
            'startDate' => ['nullable', 'date', 'required_with:endDate'],
            'endDate' => ['nullable', 'date', 'required_with:startDate', 'after:startDate'],
            'location' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'endDate.after' => 'La fecha de devolución debe ser posterior a la de recogida.',
            'endDate.required_with' => 'Indica la fecha de recogida y la de devolución.',
            'startDate.required_with' => 'Indica la fecha de recogida y la de devolución.',
        ];
    }
}
