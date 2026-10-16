<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VehiculoEstado;
use Database\Factories\VehiculoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Vehiculo extends Model
{
    /** @use HasFactory<VehiculoFactory> */
    use HasFactory;

    protected $table = 'vehiculos';

    protected $fillable = ['placa', 'marca', 'modelo', 'anio', 'tipo', 'estado'];

    protected function casts(): array
    {
        return ['estado' => VehiculoEstado::class, 'anio' => 'integer'];
    }

    /** @return HasMany<Poliza, $this> */
    public function polizas(): HasMany
    {
        return $this->hasMany(Poliza::class);
    }

    /** @return HasMany<Reserva, $this> */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    /** @return HasMany<Tarifa, $this> */
    public function tarifas(): HasMany
    {
        return $this->hasMany(Tarifa::class);
    }
}
