<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MultaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Multa extends Model
{
    /** @use HasFactory<MultaFactory> */
    use HasFactory;

    protected $table = 'multas';

    protected $fillable = ['reserva_id', 'concepto', 'monto_centavos'];

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
