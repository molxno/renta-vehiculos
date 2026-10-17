<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Reserva;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Validator;

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

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Reserva $reserva */
                $reserva = $this->route('reserva');

                if ($reserva->actaDevolucion()->exists()) {
                    $validator->errors()->add('reserva', 'La reserva ya tiene una devolución registrada.');
                }
            },
        ];
    }

    public function devueltoAt(): CarbonInterface
    {
        return Date::parse($this->string('devuelto_at')->toString());
    }

    /** @return array<array-key, mixed>|null */
    public function checklist(): ?array
    {
        return $this->filled('checklist') ? $this->array('checklist') : null;
    }

    public function observaciones(): ?string
    {
        return $this->filled('observaciones') ? $this->string('observaciones')->toString() : null;
    }
}
