<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Trm\ActualizarTrm;
use App\Http\Requests\UpdateTrmRequest;
use App\Models\Trm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
use Inertia\Response;

final class TrmController extends Controller
{
    public function edit(): Response
    {
        $trm = Trm::query()->vigente()->first();

        return inertia('Administracion/Trm/Edit', [
            'currentTrm' => $trm === null ? null : [
                'fecha' => $trm->fecha->toDateString(),
                'valor' => $trm->valor,
            ],
            'today' => Date::today()->toDateString(),
        ]);
    }

    public function update(UpdateTrmRequest $request, ActualizarTrm $actualizarTrm): RedirectResponse
    {
        $actualizarTrm->handle($request->fecha(), $request->valor());

        return to_route('admin.trm.edit');
    }
}
