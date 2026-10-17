<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NivelCombustible;
use App\Models\LicenciaConduccion;
use App\Models\Reserva;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreActaEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'entregado_at' => ['required', 'date'],
            'checklist' => ['nullable', 'array'],
            'checklist.kilometraje' => ['nullable', 'integer', 'min:0'],
            'checklist.combustible' => ['nullable', 'string', Rule::enum(NivelCombustible::class)],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'aceptacion' => ['accepted'],
            'foto' => ['nullable', 'file', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'aceptacion.accepted' => 'Debe aceptar las condiciones del contrato.',
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

                if ($reserva->actaEntrega()->exists()) {
                    $validator->errors()->add('reserva', 'La reserva ya tiene una entrega registrada.');

                    return;
                }

                if (! $reserva->estaConfirmada()) {
                    $validator->errors()->add('reserva', 'Solo se puede registrar la entrega de una reserva confirmada.');

                    return;
                }

                $this->validarLicencia($reserva, $validator);
            },
        ];
    }

    public function entregadoAt(): CarbonInterface
    {
        return Date::parse($this->string('entregado_at')->toString());
    }

    /** @return array<array-key, mixed>|null */
    public function checklist(): ?array
    {
        $checklist = $this->filled('checklist') ? $this->array('checklist') : [];

        if (array_key_exists('kilometraje', $checklist) && is_numeric($checklist['kilometraje'])) {
            $checklist['kilometraje'] = (int) $checklist['kilometraje'];
        }

        $foto = $this->fotoPath();

        if ($foto !== null) {
            $checklist['foto'] = $foto;
        }

        return $checklist === [] ? null : $checklist;
    }

    public function observaciones(): ?string
    {
        return $this->filled('observaciones') ? $this->string('observaciones')->toString() : null;
    }

    public function fotoPath(): ?string
    {
        $archivo = $this->file('foto');

        if (! $archivo instanceof UploadedFile) {
            return null;
        }

        return (string) $archivo->store('entregas', 'public');
    }

    private function validarLicencia(Reserva $reserva, Validator $validator): void
    {
        $reserva->loadMissing('user.licenciaConduccion');

        $licencia = $reserva->user->licenciaConduccion;

        if (! $licencia instanceof LicenciaConduccion) {
            $validator->errors()->add('licencia', 'La licencia de conducción no está registrada.');

            return;
        }

        if ($licencia->estaVigente()) {
            return;
        }

        $validator->errors()->add('licencia', 'La licencia de conducción está vencida.');
    }
}
