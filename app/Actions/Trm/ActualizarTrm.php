<?php

declare(strict_types=1);

namespace App\Actions\Trm;

use App\Models\Trm;

final class ActualizarTrm
{
    public function handle(string $fecha, string $valor): Trm
    {
        $trm = Trm::query()->whereDate('fecha', $fecha)->first() ?? new Trm;
        $trm->setAttribute('fecha', $fecha);
        $trm->setAttribute('valor', $valor);
        $trm->save();

        return $trm;
    }
}
