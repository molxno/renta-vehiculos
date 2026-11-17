<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReservaEstado;
use Database\Factories\ReservaHistorialEstadoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reserva_id
 * @property ReservaEstado $estado_anterior
 * @property ReservaEstado $estado_nuevo
 * @property Carbon $ocurrio_at
 */
final class ReservaHistorialEstado extends Model
{
    /** @use HasFactory<ReservaHistorialEstadoFactory> */
    use HasFactory;

    protected $table = 'reserva_historial_estados';

    protected $fillable = [
        'reserva_id', 'estado_anterior', 'estado_nuevo', 'ocurrio_at',
    ];

    protected function casts(): array
    {
        return [
            'estado_anterior' => ReservaEstado::class,
            'estado_nuevo' => ReservaEstado::class,
            'ocurrio_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Reserva, $this> */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }
}
