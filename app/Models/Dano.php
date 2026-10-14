<?php

namespace App\Models;

use Database\Factories\DanoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dano extends Model
{
    /** @use HasFactory<DanoFactory> */
    use HasFactory;

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
