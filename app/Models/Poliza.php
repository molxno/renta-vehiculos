<?php

namespace App\Models;

use Database\Factories\PolizaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Poliza extends Model
{
    /** @use HasFactory<PolizaFactory> */
    use HasFactory;

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
