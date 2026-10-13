<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Convenio extends Model
{
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
