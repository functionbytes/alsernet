<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'firstname' => $this->faker->firstName(),
            'lastname' => $this->faker->lastName(),
            // Faker::unique() solo garantiza unicidad DENTRO del proceso, no
            // contra la base de datos. Con miles de direcciones @example.* ya
            // en la tabla (residuos de ejecuciones que no aislaban) y el pool
            // limitado de safeEmail(), chocar con users_email_unique era
            // cuestión de tiempo: hacía fallar un test al azar en cada pasada
            // de la suite, y siempre uno distinto. El sufijo aleatorio lo
            // vuelve imposible sin perder el aspecto de correo real.
            'email' => Str::slug($this->faker->firstName()).'-'.Str::lower(Str::random(10)).'@example.com',
            'password' => bcrypt('password'),
            'verified' => 1,
            'available' => 1,
        ];
    }
}
