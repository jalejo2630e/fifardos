<?php

namespace Database\Factories;

use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameMatchFactory extends Factory
{
    protected $model = GameMatch::class;

    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'round' => 1,
            'player1_id' => Player::factory(),
            'player2_id' => Player::factory(),
            'status' => 'pending',
            'tv_number' => 1,
        ];
    }
}
