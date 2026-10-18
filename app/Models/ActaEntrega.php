<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ActaEntregaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $checklist
 */
final class ActaEntrega extends Model
{
    /** @use HasFactory<ActaEntregaFactory> */
    use HasFactory;

    protected $table = 'actas_entrega';

    protected $fillable = ['reserva_id', 'entregado_at', 'checklist', 'observaciones'];

    protected function casts(): array
    {
        return ['entregado_at' => 'datetime', 'checklist' => 'array'];
    }

    /** @return BelongsTo<Reserva, $this> */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }
}
