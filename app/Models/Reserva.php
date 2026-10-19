<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ReservaState;
use App\Enums\ReservaEstado;
use App\States\Reserva\CanceladaState;
use App\States\Reserva\ConfirmadaState;
use App\States\Reserva\EntregadaState;
use App\States\Reserva\FacturadaState;
use App\States\Reserva\PagadaState;
use App\States\Reserva\PendienteState;
use Database\Factories\ReservaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property ReservaEstado $estado
 * @property Carbon $inicio
 * @property Carbon $fin
 * @property int $total_centavos
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
        'total_centavos', 'moneda', 'qr_codigo',
    ];

    protected function casts(): array
    {
        return [
            'estado' => ReservaEstado::class,
            'inicio' => 'datetime',
            'fin' => 'datetime',
            'total_centavos' => 'integer',
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
        $this->estadoMaquina()->confirmar($this);
    }

    public function entregar(): void
    {
        $this->estadoMaquina()->entregar($this);
    }

    public function devolver(): void
    {
        $this->estadoMaquina()->devolver($this);
    }

    public function pagar(): void
    {
        $this->estadoMaquina()->pagar($this);
    }

    public function cancelar(): void
    {
        $this->estadoMaquina()->cancelar($this);
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

    public function mensajeBloqueoEntrega(): ?string
    {
        $this->loadMissing('user.licenciaConduccion');

        $licencia = $this->user->licenciaConduccion;

        if (! $licencia instanceof LicenciaConduccion) {
            return 'La licencia de conducción no está registrada.';
        }

        if (! $licencia->estaVigente()) {
            return 'La licencia de conducción está vencida.';
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
}
