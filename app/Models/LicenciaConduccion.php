<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LicenciaConduccionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $numero
 * @property Carbon $fecha_vencimiento
 * @property string $categoria
 */
final class LicenciaConduccion extends Model
{
    /** @use HasFactory<LicenciaConduccionFactory> */
    use HasFactory;

    protected $table = 'licencias_conduccion';

    protected $fillable = ['user_id', 'numero', 'fecha_vencimiento', 'categoria'];

    protected function casts(): array
    {
        return ['fecha_vencimiento' => 'date'];
    }

    public function estaVigente(): bool
    {
        return $this->fecha_vencimiento->gte(now()->toDateString());
    }

    public function categoriaValida(): bool
    {
        return in_array($this->categoria, (array) config('entrega.categorias_validas', []), true);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
