<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dano extends Model
{
    protected $table = 'danos';

    protected $fillable = ['reserva_id', 'descripcion', 'monto_centavos'];

    protected function casts(): array
    {
        return ['monto_centavos' => 'integer'];
    }

    /** @return BelongsTo<Reserva, $this> */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }
}
