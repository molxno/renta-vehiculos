<?php

namespace Database\Factories;

use App\Enums\UsuarioRol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'tipo_documento' => 'CC',
            'numero_documento' => fake()->unique()->numerify('##########'),
            'telefono' => fake()->numerify('3#########'),
            'rol' => UsuarioRol::Cliente,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function cliente(): static
    {
        return $this->state(fn (array $attributes): array => [
            'rol' => UsuarioRol::Cliente,
        ]);
    }

    public function coordinador(): static
    {
        return $this->state(fn (array $attributes): array => [
            'rol' => UsuarioRol::Coordinador,
        ]);
    }

    public function cajero(): static
    {
        return $this->state(fn (array $attributes): array => [
            'rol' => UsuarioRol::Cajero,
        ]);
    }

    public function administrador(): static
    {
        return $this->state(fn (array $attributes): array => [
            'rol' => UsuarioRol::Administrador,
        ]);
    }

    public function gerente(): static
    {
        return $this->state(fn (array $attributes): array => [
            'rol' => UsuarioRol::Gerente,
        ]);
    }
}
