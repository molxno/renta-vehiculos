<?php

namespace App\Models;

use App\Enums\ReservaEstado;
use Database\Factories\ReservaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reserva extends Model
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
}
