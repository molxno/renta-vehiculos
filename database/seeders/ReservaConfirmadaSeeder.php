<?php

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

        Reserva::updateOrCreate(
            ['qr_codigo' => 'QR-RESERVA-CONFIRMADA'],
            [
                'user_id' => User::where('email', 'cliente@renta.test')->value('id'),
                'vehiculo_id' => Vehiculo::where('placa', 'ABC-123')->value('id'),
                'estado' => ReservaEstado::Confirmada,
                'inicio' => '2026-02-01 09:00:00',
                'fin' => '2026-02-04 09:00:00',
                'total_centavos' => 45000000,
                'moneda' => 'COP',
            ],
        );
    }
}
