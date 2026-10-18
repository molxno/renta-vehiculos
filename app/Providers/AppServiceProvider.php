<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\CalculadoraDevolucionEstandar;
use App\Services\Devolucion\Strategies\CombustibleCargoStrategy;
use App\Services\Devolucion\Strategies\DanoCargoStrategy;
use App\Services\Devolucion\Strategies\KilometrajeCargoStrategy;
use App\Services\Devolucion\Strategies\RetrasoCargoStrategy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        foreach (config('servicios.bindings', []) as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }

        $this->app->when(CalculadoraDevolucionEstandar::class)
            ->needs('$estrategias')
            ->give(fn (): array => [
                $this->app->make(RetrasoCargoStrategy::class),
                $this->app->make(KilometrajeCargoStrategy::class),
                $this->app->make(CombustibleCargoStrategy::class),
                $this->app->make(DanoCargoStrategy::class),
            ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    private function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
