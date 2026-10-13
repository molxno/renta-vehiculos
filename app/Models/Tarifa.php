<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarifa extends Model
{
    protected $table = 'tarifas';

    protected $fillable = [
        'vehiculo_id', 'tipo_vehiculo', 'valor_diario_centavos',
        'vigente_desde', 'vigente_hasta',
    ];

    protected function casts(): array
    {
        return [
            'valor_diario_centavos' => 'integer',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    /** @return BelongsTo<Vehiculo, $this> */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }
}
