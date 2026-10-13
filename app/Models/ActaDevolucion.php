<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActaDevolucion extends Model
{
    protected $table = 'actas_devolucion';

    protected $fillable = ['reserva_id', 'devuelto_at', 'checklist', 'observaciones'];

    protected function casts(): array
    {
        return ['devuelto_at' => 'datetime', 'checklist' => 'array'];
    }

    /** @return BelongsTo<Reserva, $this> */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }
}
