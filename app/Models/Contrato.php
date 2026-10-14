<?php

namespace App\Models;

use Database\Factories\ContratoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contrato extends Model
{
    /** @use HasFactory<ContratoFactory> */
    use HasFactory;

    protected $table = 'contratos';

    protected $fillable = ['reserva_id', 'numero', 'condiciones', 'firmado_at'];

    protected function casts(): array
    {
        return ['condiciones' => 'array', 'firmado_at' => 'datetime'];
    }

    /** @return BelongsTo<Reserva, $this> */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }
}
