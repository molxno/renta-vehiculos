<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UsuarioRol;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;

final class StoreReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->rol === UsuarioRol::Cliente;
    }

    public function cliente(): User
    {
        /** @var User $cliente */
        $cliente = $this->user();

        return $cliente;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
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
