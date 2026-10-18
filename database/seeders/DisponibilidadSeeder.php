<?php

namespace Database\Seeders;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;

class DisponibilidadSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MinimumCatalogSeeder::class);

        $usuario = User::query()->where('email', 'cliente@renta.test')->sole();
        $vehiculo = Vehiculo::query()->where('placa', 'ABC-123')->sole();

        Reserva::updateOrCreate(
            ['qr_codigo' => 'QR-RESERVA-DISPONIBILIDAD'],
            [
                'user_id' => $usuario->id,
                'vehiculo_id' => $vehiculo->id,
                'estado' => ReservaEstado::Confirmada,
                'inicio' => '2026-11-10 09:00:00',
                'fin' => '2026-11-12 09:00:00',
                'total_centavos' => 45000000,
                'moneda' => 'COP',
            ],
        );
    }
}
