<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NivelCombustible;
use App\Models\Reserva;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreActaDevolucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'devuelto_at' => ['required', 'date'],
            'checklist' => ['nullable', 'array'],
            'checklist.kilometraje' => ['nullable', 'integer', 'min:0'],
            'checklist.combustible' => ['nullable', 'string', Rule::enum(NivelCombustible::class)],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'danos' => ['nullable', 'array'],
            'danos.*.descripcion' => ['required', 'string', 'max:255'],
            'danos.*.monto_centavos' => ['required', 'integer', 'min:0'],
            'evidencia' => ['nullable', 'file', 'max:2048'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $reserva = $this->route('reserva');

                if (! $reserva instanceof Reserva) {
                    return;
                }

                if ($reserva->actaDevolucion()->exists()) {
                    $validator->errors()->add('reserva', 'La reserva ya tiene una devolución registrada.');

                    return;
                }

                if (! $reserva->estaEntregada()) {
                    $validator->errors()->add('reserva', 'Solo se puede registrar la devolución de una reserva entregada.');

                    return;
                }

                $this->validarKilometrajeContraEntrega($reserva, $validator);
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
        $checklist = $this->filled('checklist') ? $this->array('checklist') : [];

        if (array_key_exists('kilometraje', $checklist) && is_numeric($checklist['kilometraje'])) {
            $checklist['kilometraje'] = (int) $checklist['kilometraje'];
        }

        $evidencia = $this->evidenciaPath();

        if ($evidencia !== null) {
            $checklist['evidencia'] = $evidencia;
        }

        return $checklist === [] ? null : $checklist;
    }

    /** @return array<int, array{descripcion: string, monto_centavos: int}> */
    public function danos(): array
    {
        $danos = [];

        foreach ($this->array('danos') as $dano) {
            if (! is_array($dano)) {
                continue;
            }

            $danos[] = [
                'descripcion' => (string) ($dano['descripcion'] ?? ''),
                'monto_centavos' => (int) ($dano['monto_centavos'] ?? 0),
            ];
        }

        return $danos;
    }

    public function observaciones(): ?string
    {
        return $this->filled('observaciones') ? $this->string('observaciones')->toString() : null;
    }

    public function evidenciaPath(): ?string
    {
        $archivo = $this->file('evidencia');

        if (! $archivo instanceof UploadedFile) {
            return null;
        }

        return (string) $archivo->store('devoluciones', 'public');
    }

    private function validarKilometrajeContraEntrega(Reserva $reserva, Validator $validator): void
    {
        if ($validator->errors()->has('checklist.kilometraje')) {
            return;
        }

        $kilometrajeFinal = $this->input('checklist.kilometraje');

        if ($kilometrajeFinal === null || $kilometrajeFinal === '') {
            return;
        }

        $kilometrajeEntrega = $reserva->checklistEntrega()['kilometraje'] ?? null;

        if (! is_numeric($kilometrajeEntrega) || (int) $kilometrajeFinal >= (int) $kilometrajeEntrega) {
            return;
        }

        $validator->errors()->add(
            'checklist.kilometraje',
            'El kilometraje final no puede ser menor al de la entrega ('.(int) $kilometrajeEntrega.' km).',
        );
    }
}
