<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;

class ReservaConfirmadaSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MinimumCatalogSeeder::class);

        $zona = (string) config('entrega.zona_horaria');
        $hoy = now()->timezone($zona)->startOfDay();
        $clienteId = User::where('email', 'cliente@renta.test')->value('id');

        Reserva::updateOrCreate(
            ['qr_codigo' => 'QR-RESERVA-CONFIRMADA'],
            [
                'user_id' => $clienteId,
                'vehiculo_id' => Vehiculo::where('placa', 'ABC-123')->value('id'),
                'estado' => ReservaEstado::Confirmada,
                'inicio' => $hoy,
                'fin' => $hoy->copy()->addDays(3),
                'total_centavos' => 45000000,
                'moneda' => 'COP',
            ],
        );

        Reserva::updateOrCreate(
            ['qr_codigo' => 'QR-RESERVA-FUTURA'],
            [
                'user_id' => $clienteId,
                'vehiculo_id' => Vehiculo::where('placa', 'GHI-789')->value('id'),
                'estado' => ReservaEstado::Confirmada,
                'inicio' => $hoy->copy()->addDays(2),
                'fin' => $hoy->copy()->addDays(5),
                'total_centavos' => 45000000,
                'moneda' => 'COP',
            ],
        );

        Reserva::updateOrCreate(
            ['qr_codigo' => 'QR-RESERVA-VENCIDA'],
            [
                'user_id' => $clienteId,
                'vehiculo_id' => Vehiculo::where('placa', 'ABC-123')->value('id'),
                'estado' => ReservaEstado::Confirmada,
                'inicio' => $hoy->copy()->subDays(10),
                'fin' => $hoy->copy()->subDays(7),
                'total_centavos' => 45000000,
                'moneda' => 'COP',
            ],
        );
    }
}
