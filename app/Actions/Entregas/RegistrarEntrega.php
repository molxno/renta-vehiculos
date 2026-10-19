<?php

declare(strict_types=1);

namespace App\Actions\Entregas;

use App\Enums\MetodoPago;
use App\Enums\PagoEstado;
use App\Enums\VehiculoEstado;
use App\Exceptions\TransicionEstadoInvalida;
use App\Models\ActaEntrega;
use App\Models\LicenciaConduccion;
use App\Models\Reserva;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RegistrarEntrega
{
    public function __construct(private EvaluarVentanaDeEntrega $evaluarVentanaDeEntrega) {}

    /**
     * Registra el acta, el contrato firmado, el anticipo y marca el vehículo alquilado.
     *
     * @param  array<array-key, mixed>|null  $checklist
     */
    public function handle(
        Reserva $reserva,
        CarbonInterface $entregadoAt,
        ?array $checklist,
        ?string $observaciones,
    ): ActaEntrega {
        $this->evaluarVentanaDeEntrega->asegurar($reserva);

        return DB::transaction(function () use ($reserva, $entregadoAt, $checklist, $observaciones): ActaEntrega {
            $condicionesCombustible = is_array($checklist) && is_string($checklist['combustible'] ?? null)
                ? $checklist['combustible']
                : 'lleno';

            $reserva->loadMissing('user.licenciaConduccion');

            $reserva->contrato()->create([
                'numero' => sprintf('CTR-%06d', $reserva->id),
                'condiciones' => [
                    'combustible' => $condicionesCombustible,
                    'kilometraje_incluido' => (int) config('entrega.kilometraje_incluido'),
                ],
                'firmado_at' => $entregadoAt,
            ]);

            $coordinador = Auth::user();

            $acta = $reserva->actaEntrega()->create([
                'entregado_at' => $entregadoAt,
                'checklist' => $checklist,
                'observaciones' => $observaciones,
                'coordinador_id' => $coordinador instanceof User ? $coordinador->id : null,
                'firmante_nombre' => $reserva->user->name,
                'firmado_at' => $entregadoAt,
                'licencia_resultado' => $this->resultadoLicencia($reserva),
            ]);

            $acta->update([
                'numero' => sprintf('%s-%d', (string) config('entrega.prefijo_acta'), $acta->id),
            ]);

            $reserva->pagos()->create([
                'estado' => PagoEstado::Aprobado,
                'metodo' => MetodoPago::Efectivo,
                'monto_centavos' => $reserva->anticipoCentavos(),
                'referencia' => sprintf('ANT-%06d', $reserva->id),
                'mensaje' => 'Anticipo de entrega',
            ]);

            try {
                $reserva->entregar();
            } catch (TransicionEstadoInvalida $excepcion) {
                throw ValidationException::withMessages([
                    'reserva' => $excepcion->getMessage(),
                ]);
            }

            $reserva->vehiculo()->update(['estado' => VehiculoEstado::Alquilado]);

            return $acta->refresh();
        });
    }

    private function resultadoLicencia(Reserva $reserva): string
    {
        $licencia = $reserva->user->licenciaConduccion;

        if (! $licencia instanceof LicenciaConduccion) {
            return 'Licencia no verificada';
        }

        $vigencia = $licencia->estaVigente() ? 'vigente' : 'vencida';
        $categoria = $licencia->categoriaValida() ? 'válida' : 'no válida';

        return sprintf(
            'Licencia %s %s, categoría %s %s',
            $licencia->numero,
            $vigencia,
            $licencia->categoria,
            $categoria,
        );
    }
}
