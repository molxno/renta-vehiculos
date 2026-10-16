<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ConvenioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Convenio extends Model
{
    /** @use HasFactory<ConvenioFactory> */
    use HasFactory;

    protected $table = 'convenios';

    protected $fillable = ['nombre', 'descuento_porcentaje', 'activo'];

    protected function casts(): array
    {
        return ['descuento_porcentaje' => 'integer', 'activo' => 'boolean'];
    }

    /** @return HasMany<Reserva, $this> */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }
}
