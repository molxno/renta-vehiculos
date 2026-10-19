<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ActaEntregaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reserva_id
 * @property string|null $numero
 * @property int|null $coordinador_id
 * @property string|null $firmante_nombre
 * @property Carbon|null $firmado_at
 * @property string|null $licencia_resultado
 * @property array<string, mixed>|null $checklist
 * @property Carbon|null $entregado_at
 * @property string|null $observaciones
 * @property User|null $coordinador
 */
final class ActaEntrega extends Model
{
    /** @use HasFactory<ActaEntregaFactory> */
    use HasFactory;

    protected $table = 'actas_entrega';

    protected $fillable = [
        'reserva_id',
        'numero',
        'coordinador_id',
        'firmante_nombre',
        'firmado_at',
        'licencia_resultado',
        'entregado_at',
        'checklist',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'entregado_at' => 'datetime',
            'firmado_at' => 'datetime',
            'checklist' => 'array',
        ];
    }

    /** @return BelongsTo<Reserva, $this> */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    /** @return BelongsTo<User, $this> */
    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinador_id');
    }
}
