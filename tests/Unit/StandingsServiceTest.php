<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use App\Services\StandingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StandingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_standings_with_no_matches(): void
    {
        $tournament = Tournament::factory()->create();
        Player::factory()->count(4)->for($tournament)->create();

        $standings = app(StandingsService::class)->calculate($tournament);

        $this->assertCount(4, $standings);
        foreach ($standings as $row) {
            $this->assertEquals(0, $row['pts']);
            $this->assertEquals(0, $row['pj']);
        }
    }

    public function test_calculates_standings_with_one_match(): void
    {
        $tournament = Tournament::factory()->create();
        $p1 = Player::factory()->for($tournament)->create(['name' => 'Alice']);
        $p2 = Player::factory()->for($tournament)->create(['name' => 'Bob']);

        GameMatch::factory()->for($tournament)->create([
            'player1_id' => $p1->id,
            'player2_id' => $p2->id,
            'score1' => 3,
            'score2' => 1,
            'status' => 'finished',
        ]);

        $standings = app(StandingsService::class)->calculate($tournament);

        $this->assertCount(2, $standings);
        $this->assertEquals('Alice', $standings[0]['player_name']);
        $this->assertEquals(3, $standings[0]['pts']);
        $this->assertEquals(1, $standings[0]['pj']);
        $this->assertEquals(1, $standings[0]['pg']);
        $this->assertEquals(3, $standings[0]['gf']);
        $this->assertEquals(1, $standings[0]['gc']);
        $this->assertEquals(2, $standings[0]['dg']);
        $this->assertEquals('Bob', $standings[1]['player_name']);
        $this->assertEquals(0, $standings[1]['pts']);
    }

    public function test_calculates_draw_correctly(): void
    {
        $tournament = Tournament::factory()->create();
        $p1 = Player::factory()->for($tournament)->create();
        $p2 = Player::factory()->for($tournament)->create();

        GameMatch::factory()->for($tournament)->create([
            'player1_id' => $p1->id,
            'player2_id' => $p2->id,
            'score1' => 2,
            'score2' => 2,
            'status' => 'finished',
        ]);

        $standings = app(StandingsService::class)->calculate($tournament);

        $this->assertEquals(1, $standings[0]['pts']);
        $this->assertEquals(1, $standings[0]['pe']);
        $this->assertEquals(1, $standings[1]['pts']);
        $this->assertEquals(1, $standings[1]['pe']);
    }

    public function test_sorts_by_pts_then_goal_difference_then_goals_for(): void
    {
        $tournament = Tournament::factory()->create();
        $p1 = Player::factory()->for($tournament)->create(['name' => 'A']);
        $p2 = Player::factory()->for($tournament)->create(['name' => 'B']);
        $p3 = Player::factory()->for($tournament)->create(['name' => 'C']);

        GameMatch::factory()->for($tournament)->create(['player1_id' => $p1->id, 'player2_id' => $p2->id, 'score1' => 1, 'score2' => 0, 'status' => 'finished']);
        GameMatch::factory()->for($tournament)->create(['player1_id' => $p1->id, 'player2_id' => $p3->id, 'score1' => 0, 'score2' => 0, 'status' => 'finished']);
        GameMatch::factory()->for($tournament)->create(['player1_id' => $p2->id, 'player2_id' => $p3->id, 'score1' => 2, 'score2' => 2, 'status' => 'finished']);

        $standings = app(StandingsService::class)->calculate($tournament);

        $this->assertEquals('A', $standings[0]['player_name']); // 4 pts
        $this->assertEquals('C', $standings[1]['player_name']); // 1 pt, dg 0
        $this->assertEquals('B', $standings[2]['player_name']); // 1 pt, dg -1
        $this->assertEquals(4, $standings[0]['pts']);
        $this->assertEquals(2, $standings[1]['pts']);
        $this->assertEquals(1, $standings[2]['pts']);
    }

    public function test_ignores_pending_matches(): void
    {
        $tournament = Tournament::factory()->create();
        $p1 = Player::factory()->for($tournament)->create();
        $p2 = Player::factory()->for($tournament)->create();

        GameMatch::factory()->for($tournament)->create([
            'player1_id' => $p1->id,
            'player2_id' => $p2->id,
            'status' => 'pending',
        ]);

        $standings = app(StandingsService::class)->calculate($tournament);

        $this->assertEquals(0, $standings[0]['pj']);
        $this->assertEquals(0, $standings[0]['pts']);
    }
}
