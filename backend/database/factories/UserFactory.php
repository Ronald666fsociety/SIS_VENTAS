<?php

namespace Database\Factories;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => (string) fake()->unique()->numerify('7#######'),
            'password' => 'password123',
            'rol' => Rol::Cliente,
            'activo' => true,
        ];
    }

    public function rol(Rol $rol): static
    {
        return $this->state(fn () => ['rol' => $rol]);
    }
}
