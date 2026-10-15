<?php

namespace Database\Factories;

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
            'role' => User::ROLE_CUSTOMER,
            'phone' => '081'.fake()->numerify('#########'),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
        ]);
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

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => ['role' => User::ROLE_OWNER]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => User::ROLE_SUPER_ADMIN]);
    }

    public function sales(): static
    {
        return $this->state(fn (array $attributes) => ['role' => User::ROLE_SALES]);
    }

    public function customerService(): static
    {
        return $this->state(fn (array $attributes) => ['role' => User::ROLE_CUSTOMER_SERVICE]);
    }

    public function mechanic(): static
    {
        return $this->state(fn (array $attributes) => ['role' => User::ROLE_MECHANIC]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
