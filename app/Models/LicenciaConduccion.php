<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenciaConduccion extends Model
{
    protected $table = 'licencias_conduccion';

    protected $fillable = ['user_id', 'numero', 'fecha_vencimiento', 'categoria'];

    protected function casts(): array
    {
        return ['fecha_vencimiento' => 'date'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
