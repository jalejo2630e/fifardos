<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgentApiControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function authenticate(): void
    {
        Sanctum::actingAs($this->user, ['agent:access']);
    }

    public function test_schema_returns_endpoints(): void
    {
        $this->authenticate();
        $response = $this->getJson('/api/agent/schema');

        $response->assertOk()
            ->assertJsonStructure([
                'api_name', 'version', 'base_url', 'authentication', 'endpoints',
            ]);
    }

    public function test_tournaments_requires_auth(): void
    {
        $this->getJson('/api/agent/tournaments')->assertUnauthorized();
    }

    public function test_tournaments_returns_empty_list(): void
    {
        $this->authenticate();
        $response = $this->getJson('/api/agent/tournaments');

        $response->assertOk()
            ->assertJson(['success' => true, 'count' => 0, 'data' => []]);
    }

    public function test_tournaments_returns_tournaments_with_leader(): void
    {
        $tournament = Tournament::factory()->for($this->user)->create(['name' => 'Copa Test']);
        $p1 = Player::factory()->for($tournament)->create(['name' => 'Alice']);
        $p2 = Player::factory()->for($tournament)->create(['name' => 'Bob']);
        GameMatch::factory()->for($tournament)->create([
            'player1_id' => $p1->id,
            'player2_id' => $p2->id,
            'score1' => 2,
            'score2' => 1,
            'status' => 'finished',
        ]);

        $this->authenticate();
        $response = $this->getJson('/api/agent/tournaments');

        $response->assertOk();
        $this->assertCount(1, $response['data']);
        $this->assertEquals('Copa Test', $response['data'][0]['name']);
        $this->assertNotNull($response['data'][0]['leader']);
        $this->assertEquals('Alice', $response['data'][0]['leader']['player_name']);
    }

    public function test_standings_returns_full_table(): void
    {
        $tournament = Tournament::factory()->for($this->user)->create();
        $p1 = Player::factory()->for($tournament)->create();
        $p2 = Player::factory()->for($tournament)->create();
        $p3 = Player::factory()->for($tournament)->create();
        GameMatch::factory()->for($tournament)->create([
            'player1_id' => $p1->id,
            'player2_id' => $p2->id,
            'round' => 1,
            'score1' => 1,
            'score2' => 0,
            'status' => 'finished',
        ]);
        GameMatch::factory()->for($tournament)->create([
            'player1_id' => $p1->id,
            'player2_id' => $p3->id,
            'round' => 1,
            'score1' => null,
            'score2' => null,
            'status' => 'pending',
        ]);

        $this->authenticate();
        $response = $this->getJson("/api/agent/tournaments/{$tournament->id}/standings");

        $response->assertOk();
        $this->assertCount(3, $response['standings']);
        $this->assertFalse($response['all_matches_played']);
    }

    public function test_standings_404(): void
    {
        $this->authenticate();
        $this->getJson('/api/agent/tournaments/999/standings')->assertNotFound();
    }

    public function test_top_scorer_returns_leader(): void
    {
        $tournament = Tournament::factory()->for($this->user)->create();
        $p1 = Player::factory()->for($tournament)->create(['name' => 'Alice']);
        $p2 = Player::factory()->for($tournament)->create(['name' => 'Bob']);
        GameMatch::factory()->for($tournament)->create([
            'player1_id' => $p1->id,
            'player2_id' => $p2->id,
            'score1' => 5,
            'score2' => 2,
            'status' => 'finished',
        ]);

        $this->authenticate();
        $response = $this->getJson("/api/agent/tournaments/{$tournament->id}/top-scorer");

        $response->assertOk();
        $this->assertEquals('Alice', $response['data']['player_name']);
        $this->assertEquals(5, $response['data']['total_goals']);
    }

    public function test_matches_returns_filtered_list(): void
    {
        $tournament = Tournament::factory()->for($this->user)->create();
        $p1 = Player::factory()->for($tournament)->create();
        $p2 = Player::factory()->for($tournament)->create();
        GameMatch::factory()->for($tournament)->create([
            'player1_id' => $p1->id,
            'player2_id' => $p2->id,
            'round' => 1,
            'status' => 'pending',
        ]);

        $this->authenticate();
        $response = $this->getJson("/api/agent/tournaments/{$tournament->id}/matches?status=pending");

        $response->assertOk();
        $this->assertCount(1, $response['data']);
        $this->assertEquals('pending', $response['data'][0]['status']);
    }

    public function test_player_returns_stats(): void
    {
        $tournament = Tournament::factory()->for($this->user)->create();
        $player = Player::factory()->for($tournament)->create(['name' => 'Alice']);

        $this->authenticate();
        $response = $this->getJson("/api/agent/players/{$player->id}");

        $response->assertOk();
        $this->assertEquals('Alice', $response['data']['name']);
        $this->assertArrayHasKey('stats', $response['data']);
    }

    public function test_player_404(): void
    {
        $this->authenticate();
        $this->getJson('/api/agent/players/999')->assertNotFound();
    }

    public function test_rate_limiting(): void
    {
        $this->authenticate();
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/agent/schema');
            if ($response->status() === 429) {
                $response->assertStatus(429);
                return;
            }
        }
        $this->fail('Rate limit was not hit after 61 requests');
    }
}
