<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;

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

    public function inicio(): CarbonInterface
    {
        return Date::parse($this->string('inicio')->toString());
    }

    public function fin(): CarbonInterface
    {
        return Date::parse($this->string('fin')->toString());
    }

    public function convenioId(): ?int
    {
        if (! $this->filled('convenio_id')) {
            return null;
        }

        return $this->integer('convenio_id');
    }

    public function moneda(): ?string
    {
        $moneda = $this->string('moneda')->toString();

        return $moneda === '' ? null : $moneda;
    }
}
