<?php

namespace App\Providers;

use App\Contracts\CalculadoraDevolucion;
use App\Contracts\DisponibilidadService;
use App\Contracts\GeneradorQr;
use App\Contracts\PasarelaPago;
use App\Contracts\TarifaService;
use App\Fakes\FakeCalculadoraDevolucion;
use App\Fakes\FakeDisponibilidadService;
use App\Fakes\FakeGeneradorQr;
use App\Fakes\FakePasarelaPago;
use App\Fakes\FakeTarifaService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DisponibilidadService::class, FakeDisponibilidadService::class);
        $this->app->bind(TarifaService::class, FakeTarifaService::class);
        $this->app->bind(PasarelaPago::class, FakePasarelaPago::class);
        $this->app->bind(GeneradorQr::class, FakeGeneradorQr::class);
        $this->app->bind(CalculadoraDevolucion::class, FakeCalculadoraDevolucion::class);
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
    protected function configureDefaults(): void
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
