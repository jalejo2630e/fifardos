<?php

namespace Database\Factories;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentFactory extends Factory
{
    protected $model = Tournament::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word() . ' Cup',
            'consoles_count' => fake()->numberBetween(1, 4),
            'status' => 'in_progress',
            'color' => fake()->hexColor(),
        ];
    }
}
