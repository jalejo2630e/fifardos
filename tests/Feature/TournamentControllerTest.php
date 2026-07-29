<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_redirects_to_login_for_guests(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_index_shows_empty_state(): void
    {
        $this->actingAs($this->user)
            ->get(route('tournaments.index'))
            ->assertInertia(fn($page) => $page
                ->component('Tournaments/Index')
                ->where('tournaments', [])
            );
    }

    public function test_index_shows_user_tournaments(): void
    {
        Tournament::factory()->for($this->user)->create(['name' => 'My Tournament']);

        $this->actingAs($this->user)
            ->get(route('tournaments.index'))
            ->assertInertia(fn($page) => $page
                ->component('Tournaments/Index')
                ->has('tournaments', 1)
                ->where('tournaments.0.name', 'My Tournament')
            );
    }

    public function test_only_shows_own_tournaments(): void
    {
        Tournament::factory()->for($this->user)->create(['name' => 'Mine']);
        $other = User::factory()->create();
        Tournament::factory()->for($other)->create(['name' => 'Theirs']);

        $this->actingAs($this->user)
            ->get(route('tournaments.index'))
            ->assertInertia(fn($page) => $page
                ->component('Tournaments/Index')
                ->has('tournaments', 1)
                ->where('tournaments.0.name', 'Mine')
            );
    }

    public function test_create_shows_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('tournaments.create'))
            ->assertInertia(fn($page) => $page->component('Tournaments/Create'));
    }

    public function test_store_creates_tournament_with_players_and_matches(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('tournaments.store'), [
            'name' => 'Test Cup',
            'consoles_count' => 2,
            'players' => ['Alice', 'Bob', 'Charlie', 'Diana'],
        ]);

        $this->assertDatabaseHas('tournaments', ['name' => 'Test Cup', 'user_id' => $this->user->id]);
        $this->assertDatabaseCount('players', 4);
        $this->assertDatabaseCount('matches', 6); // 4 players = 6 round-robin matches

        $tournament = Tournament::where('name', 'Test Cup')->first();
        $response->assertRedirect(route('tournaments.show', $tournament));
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->user)
            ->post(route('tournaments.store'), [])
            ->assertSessionHasErrors(['name', 'consoles_count', 'players']);
    }

    public function test_store_requires_at_least_two_players(): void
    {
        $this->actingAs($this->user)
            ->post(route('tournaments.store'), [
                'name' => 'Test',
                'consoles_count' => 1,
                'players' => ['Alice'],
            ])
            ->assertSessionHasErrors(['players']);
    }

    public function test_show_displays_tournament(): void
    {
        $tournament = Tournament::factory()->for($this->user)->create(['name' => 'World Cup']);
        Player::factory()->count(2)->for($tournament)->create();

        $this->actingAs($this->user)
            ->get(route('tournaments.show', $tournament))
            ->assertInertia(fn($page) => $page
                ->component('Tournaments/Show')
                ->where('tournament.name', 'World Cup')
            );
    }

    public function test_show_allows_any_authenticated_user(): void
    {
        $other = User::factory()->create();
        $tournament = Tournament::factory()->for($other)->create();

        $this->actingAs($this->user)
            ->get(route('tournaments.show', $tournament))
            ->assertOk();
    }

    public function test_update_score_marks_match_finished(): void
    {
        $tournament = Tournament::factory()->for($this->user)->create();
        $p1 = Player::factory()->for($tournament)->create();
        $p2 = Player::factory()->for($tournament)->create();
        $match = GameMatch::factory()->for($tournament)->create([
            'player1_id' => $p1->id,
            'player2_id' => $p2->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('matches.score.update', [$tournament, $match]), [
                'score1' => 4,
                'score2' => 2,
            ]);

        $match->refresh();
        $this->assertEquals('finished', $match->status);
        $this->assertEquals(4, $match->score1);
        $this->assertEquals(2, $match->score2);
        $this->assertNotNull($match->played_at);
    }

    public function test_edit_score_resets_match_to_pending(): void
    {
        $tournament = Tournament::factory()->for($this->user)->create();
        $p1 = Player::factory()->for($tournament)->create();
        $p2 = Player::factory()->for($tournament)->create();
        $match = GameMatch::factory()->for($tournament)->create([
            'player1_id' => $p1->id,
            'player2_id' => $p2->id,
            'score1' => 3,
            'score2' => 1,
            'status' => 'finished',
        ]);

        $this->actingAs($this->user)
            ->post(route('matches.score.edit', [$tournament, $match]));

        $match->refresh();
        $this->assertEquals('pending', $match->status);
        $this->assertNull($match->score1);
        $this->assertNull($match->score2);
    }

    public function test_destroy_deletes_tournament(): void
    {
        $tournament = Tournament::factory()->for($this->user)->create();
        Player::factory()->count(2)->for($tournament)->create();

        $this->actingAs($this->user)
            ->delete(route('tournaments.destroy', $tournament))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('tournaments', ['id' => $tournament->id]);
    }
}
