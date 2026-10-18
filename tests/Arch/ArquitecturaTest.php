<?php

arch()->preset()->php();
arch()->preset()->security();
$laravelPreset = arch()->preset()->laravel();
$laravelPreset->ignoring('App\Http\Controllers\ReservaController');
$strictPreset = arch()->preset()->strict();
$strictPreset->ignoring('App\Http\Controllers\Controller');

arch('app uses strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('app has no debug helpers')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'ray']);

arch('controllers do not access persistence directly')
    ->expect('App\Http\Controllers')
    ->not->toUse(['DB', 'Illuminate\Support\Facades\DB', 'Illuminate\Database\Eloquent\Builder']);

arch('models do not depend on http layers')
    ->expect('App\Models')
    ->not->toUse(['App\Http\Controllers', 'App\Http\Requests']);

arch('contracts contain only interfaces')
    ->expect('App\Contracts')
    ->toBeInterfaces();

arch('enums contain only enums')
    ->expect('App\Enums')
    ->toBeEnums();

arch('actions are final')
    ->expect('App\Actions')
    ->classes()
    ->toBeFinal();
