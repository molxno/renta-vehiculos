<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Poliza extends Model
{
    protected $table = 'polizas';

    protected $fillable = [
        'vehiculo_id', 'numero', 'aseguradora', 'vigencia_desde', 'vigencia_hasta',
    ];

    protected function casts(): array
    {
        return ['vigencia_desde' => 'date', 'vigencia_hasta' => 'date'];
    }

    /** @return BelongsTo<Vehiculo, $this> */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }
}
