<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ReservaState;
use App\Enums\PagoEstado;
use App\Enums\ReservaEstado;
use App\Events\ReservaEstadoCambiado;
use App\States\Reserva\CanceladaState;
use App\States\Reserva\ConfirmadaState;
use App\States\Reserva\EntregadaState;
use App\States\Reserva\FacturadaState;
use App\States\Reserva\PagadaState;
use App\States\Reserva\PendienteState;
use Carbon\CarbonInterface;
use Database\Factories\ReservaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

/**
 * @property ReservaEstado $estado
 * @property Carbon $inicio
 * @property Carbon $fin
 * @property int $total_centavos
 * @property int|null $renta_neta_centavos
 * @property string $moneda
 * @property string|null $qr_codigo
 * @property User $user
 * @property Vehiculo $vehiculo
 */
final class Reserva extends Model
{
    /** @use HasFactory<ReservaFactory> */
    use HasFactory;

    protected $table = 'reservas';

    protected $fillable = [
        'user_id', 'vehiculo_id', 'convenio_id', 'estado', 'inicio', 'fin',
        'total_centavos', 'renta_neta_centavos', 'moneda', 'qr_codigo',
    ];

    protected function casts(): array
    {
        return [
            'estado' => ReservaEstado::class,
            'inicio' => 'datetime',
            'fin' => 'datetime',
            'total_centavos' => 'integer',
            'renta_neta_centavos' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Vehiculo, $this> */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    /** @return BelongsTo<Convenio, $this> */
    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class);
    }

    /** @return HasOne<Contrato, $this> */
    public function contrato(): HasOne
    {
        return $this->hasOne(Contrato::class);
    }

    /** @return HasOne<ActaEntrega, $this> */
    public function actaEntrega(): HasOne
    {
        return $this->hasOne(ActaEntrega::class);
    }

    /** @return HasOne<ActaDevolucion, $this> */
    public function actaDevolucion(): HasOne
    {
        return $this->hasOne(ActaDevolucion::class);
    }

    /** @return HasOne<Factura, $this> */
    public function factura(): HasOne
    {
        return $this->hasOne(Factura::class);
    }

    /** @return HasMany<Pago, $this> */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    /** @return HasMany<Multa, $this> */
    public function multas(): HasMany
    {
        return $this->hasMany(Multa::class);
    }

    /** @return HasMany<Dano, $this> */
    public function danos(): HasMany
    {
        return $this->hasMany(Dano::class);
    }

    /** @return HasMany<ReservaHistorialEstado, $this> */
    public function historialEstados(): HasMany
    {
        return $this->hasMany(ReservaHistorialEstado::class)
            ->orderBy('ocurrio_at')
            ->orderBy('id');
    }

    public function fechaHito(ReservaEstado $estado): ?CarbonInterface
    {
        $this->loadMissing('historialEstados');

        return $this->historialEstados
            ->first(
                fn (ReservaHistorialEstado $historial): bool => $historial->estado_nuevo === $estado,
            )
            ?->ocurrio_at;
    }

    public function estadoMaquina(): ReservaState
    {
        return match ($this->estado) {
            ReservaEstado::Pendiente => new PendienteState,
            ReservaEstado::Confirmada => new ConfirmadaState,
            ReservaEstado::Entregada => new EntregadaState,
            ReservaEstado::Facturada => new FacturadaState,
            ReservaEstado::Pagada => new PagadaState,
            ReservaEstado::Cancelada, ReservaEstado::Devuelta => new CanceladaState,
        };
    }

    public function confirmar(): void
    {
        $this->ejecutarTransicion(fn () => $this->estadoMaquina()->confirmar($this));
    }

    public function entregar(): void
    {
        $this->ejecutarTransicion(fn () => $this->estadoMaquina()->entregar($this));
    }

    public function devolver(): void
    {
        $this->ejecutarTransicion(fn () => $this->estadoMaquina()->devolver($this));
    }

    public function pagar(): void
    {
        $this->ejecutarTransicion(fn () => $this->estadoMaquina()->pagar($this));
    }

    public function cancelar(): void
    {
        $this->ejecutarTransicion(fn () => $this->estadoMaquina()->cancelar($this));
    }

    public function estaEntregada(): bool
    {
        return $this->estado === ReservaEstado::Entregada;
    }

    public function estaConfirmada(): bool
    {
        return $this->estado === ReservaEstado::Confirmada;
    }

    public function anticipoCentavos(): int
    {
        return intdiv($this->total_centavos * (int) config('entrega.anticipo_porcentaje'), 100);
    }

    public function rentaNetaCentavos(): int
    {
        return $this->renta_neta_centavos ?? $this->total_centavos;
    }

    /**
     * @return Collection<int, Pago>
     */
    public function anticiposAprobados(): Collection
    {
        $this->loadMissing('pagos');

        return $this->pagos
            ->filter(fn (Pago $pago): bool => $pago->estado === PagoEstado::Aprobado
                && is_string($pago->referencia)
                && str_starts_with($pago->referencia, 'ANT-'))
            ->values();
    }

    public function montoPagosAprobadosCentavos(): int
    {
        $this->loadMissing('pagos');

        return (int) $this->pagos
            ->filter(fn (Pago $pago): bool => $pago->estado === PagoEstado::Aprobado)
            ->sum('monto_centavos');
    }

    public function saldoAPagarCentavos(int $totalFacturaCentavos): int
    {
        return max(0, $totalFacturaCentavos - $this->montoPagosAprobadosCentavos());
    }

    /**
     * @return array{
     *     anticipos: list<array{referencia: string, monto_centavos: int}>,
     *     anticipos_centavos: int,
     *     saldo_centavos: int
     * }
     */
    public function resumenAnticiposYSaldo(int $totalCentavos): array
    {
        $anticipos = array_values($this->anticiposAprobados()
            ->map(fn (Pago $pago): array => [
                'referencia' => (string) $pago->referencia,
                'monto_centavos' => $pago->monto_centavos,
            ])
            ->all());

        return [
            'anticipos' => $anticipos,
            'anticipos_centavos' => (int) array_sum(array_column($anticipos, 'monto_centavos')),
            'saldo_centavos' => $this->saldoAPagarCentavos($totalCentavos),
        ];
    }

    /**
     * @return array{
     *     anticipos: list<array{referencia: string, monto_centavos: int}>,
     *     anticiposCents: int,
     *     saldoCents: int
     * }
     */
    public function liquidacionInertia(int $totalCentavos): array
    {
        $resumen = $this->resumenAnticiposYSaldo($totalCentavos);

        return [
            'anticipos' => $resumen['anticipos'],
            'anticiposCents' => $resumen['anticipos_centavos'],
            'saldoCents' => $resumen['saldo_centavos'],
        ];
    }

    public function mensajeBloqueoEntrega(): ?string
    {
        $this->loadMissing('user.licenciaConduccion');

        $licencia = $this->user->licenciaConduccion;

        if (! $licencia instanceof LicenciaConduccion) {
            return 'La licencia de conducción no está registrada. El cliente debe registrarla en su cuenta.';
        }

        if (! $licencia->estaVigente()) {
            return 'La licencia de conducción está vencida. El cliente debe actualizarla en su cuenta.';
        }

        if (! $licencia->categoriaValida()) {
            return 'La categoría de la licencia no es válida para entregar el vehículo.';
        }

        if ($this->actaEntrega()->exists()) {
            return 'La reserva ya tiene una entrega registrada.';
        }

        if (! $this->estaConfirmada()) {
            return 'Solo se puede registrar la entrega de una reserva confirmada.';
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function checklistEntrega(): array
    {
        if (! $this->actaEntrega()->exists()) {
            return [];
        }

        return $this->actaEntrega()->first()->checklist ?? [];
    }

    /** @return array<string, mixed> */
    public function condicionesContrato(): array
    {
        if (! $this->contrato()->exists()) {
            return [];
        }

        return $this->contrato()->first()->condiciones ?? [];
    }

    /**
     * @param  callable(): void  $transicion
     */
    private function ejecutarTransicion(callable $transicion): void
    {
        $estadoAnterior = $this->estado;
        $transicion();

        ReservaEstadoCambiado::dispatch(
            $this,
            $estadoAnterior,
            $this->estado,
            Date::now(),
        );
    }
}
