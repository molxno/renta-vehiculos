<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MinimumCatalogSeeder::class,
            ReservaConfirmadaSeeder::class,
            AlquilerEntregadoSeeder::class,
            DevolucionFacturadaSeeder::class,
            PagoRechazadoSeeder::class,
        ]);
    }
}
