<?php

namespace Database\Factories;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reserva>
 */
class ReservaFactory extends Factory
{
    protected $model = Reserva::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vehiculo_id' => Vehiculo::factory(),
            'convenio_id' => null,
            'estado' => ReservaEstado::Confirmada,
            'inicio' => now()->addDay()->startOfDay(),
            'fin' => now()->addDays(3)->startOfDay(),
            'total_centavos' => 45000000,
            'moneda' => 'COP',
            'qr_codigo' => 'QR-'.fake()->unique()->numerify('########'),
        ];
    }
}
