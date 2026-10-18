<?php

declare(strict_types=1);

namespace App\Actions\Trm;

use App\Models\Trm;

final class ActualizarTrm
{
    public function handle(string $fecha, string $valor): Trm
    {
        $trm = Trm::query()->whereDate('fecha', $fecha)->first();

        if ($trm === null) {
            return Trm::query()->create([
                'fecha' => $fecha,
                'valor' => $valor,
            ]);
        }

        $trm->update(['valor' => $valor]);

        return $trm->refresh();
    }
}
