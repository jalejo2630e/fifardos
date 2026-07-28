<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'name' => fake()->firstName(),
        ];
    }
}
