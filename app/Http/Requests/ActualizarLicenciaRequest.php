<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Support\ReglasLicenciaConduccion;
use Illuminate\Foundation\Http\FormRequest;

final class ActualizarLicenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ReglasLicenciaConduccion::rules();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ReglasLicenciaConduccion::messages();
    }
}
