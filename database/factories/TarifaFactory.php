<?php

namespace Database\Factories;

use App\Models\Tarifa;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tarifa>
 */
class TarifaFactory extends Factory
{
    protected $model = Tarifa::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehiculo_id' => Vehiculo::factory(),
            'tipo_vehiculo' => 'sedan',
            'valor_diario_centavos' => 15000000,
            'vigente_desde' => now()->startOfYear()->toDateString(),
            'vigente_hasta' => null,
        ];
    }
}
