<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPlayerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_displays_available_tournaments(): void
    {
        Tournament::factory()->create(['name' => 'Open Cup', 'status' => 'setup', 'max_players' => 10]);
        Tournament::factory()->create(['name' => 'Full Cup', 'status' => 'setup', 'max_players' => 2]);
        Tournament::factory()->create(['name' => 'Closed League', 'status' => 'in_progress']);

        $response = $this->get(route('players.public.create'));

        $response->assertInertia(fn($page) => $page
            ->component('Public/Register')
            ->has('tournaments', 2)
            ->where('tournaments.0.name', 'Open Cup')
        );
    }

    public function test_store_creates_player(): void
    {
        $tournament = Tournament::factory()->create(['status' => 'setup', 'max_players' => 10]);

        $response = $this->post(route('players.public.store'), [
            'tournament_id' => $tournament->id,
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'psn_id' => 'juanp_psn',
            'email' => 'juan@example.com',
            'preferred_team' => 'Barcelona',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('players', [
            'tournament_id' => $tournament->id,
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'username' => 'jperez',
        ]);
    }

    public function test_store_validates_password_has_uppercase(): void
    {
        $tournament = Tournament::factory()->create(['status' => 'setup']);

        $response = $this->post(route('players.public.store'), [
            'tournament_id' => $tournament->id,
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'psn_id' => 'juanp_psn',
            'email' => 'juan@example.com',
            'preferred_team' => 'Barcelona',
            'password' => 'lowercase1',
            'password_confirmation' => 'lowercase1',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_store_validates_unique_psn_per_tournament(): void
    {
        $tournament = Tournament::factory()->create(['status' => 'setup']);
        Player::factory()->for($tournament)->create(['psn_id' => 'existing_psn']);

        $response = $this->post(route('players.public.store'), [
            'tournament_id' => $tournament->id,
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'psn_id' => 'existing_psn',
            'email' => 'juan@example.com',
            'preferred_team' => 'Barcelona',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ]);

        $response->assertSessionHasErrors(['psn_id']);
    }

    public function test_store_rejects_full_tournament(): void
    {
        $tournament = Tournament::factory()->create(['status' => 'setup', 'max_players' => 1]);
        Player::factory()->for($tournament)->create();

        $response = $this->post(route('players.public.store'), [
            'tournament_id' => $tournament->id,
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'psn_id' => 'juanp_psn',
            'email' => 'juan@example.com',
            'preferred_team' => 'Barcelona',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ]);

        $response->assertSessionHasErrors(['tournament_id']);
    }

    public function test_store_rejects_non_setup_tournament(): void
    {
        $tournament = Tournament::factory()->create(['status' => 'in_progress']);

        $response = $this->post(route('players.public.store'), [
            'tournament_id' => $tournament->id,
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'psn_id' => 'juanp_psn',
            'email' => 'juan@example.com',
            'preferred_team' => 'Barcelona',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ]);

        $response->assertSessionHasErrors(['tournament_id']);
    }
}
