<?php

namespace Tests\Feature;

use App\Models\SportRuleDefinition;
use App\Models\Tournament;
use App\Models\User;
use Database\Seeders\SportRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->seed(SportRulesSeeder::class);
    }

    public function test_create_exposes_rule_definitions_grouped_by_sport(): void
    {
        $this->actingAs($this->user)
            ->get(route('tournaments.create'))
            ->assertInertia(fn ($page) => $page
                ->component('Tournaments/Create')
                ->has('rules.volleyball', 6)
                ->has('rules.soccer')
            );
    }

    public function test_store_persists_mode_and_rules(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('tournaments.store'), [
            'name' => 'Copa Voley',
            'sport' => 'volleyball',
            'mode' => 'physical',
            'consoles_count' => 2,
            'teams' => [
                ['name' => 'A', 'players' => []],
                ['name' => 'B', 'players' => []],
            ],
            'rules' => [
                'sets_para_ganar_partido' => '3',
                'puntos_por_set' => '25',
                'diferencia_minima' => '2',
                'libero_habilitado' => '1',
                'rotacion_obligatoria' => '0',
            ],
        ]);

        $tournament = Tournament::where('name', 'Copa Voley')->first();
        $response->assertRedirect(route('tournaments.show', $tournament));

        $this->assertSame('physical', $tournament->mode);
        $this->assertDatabaseHas('tournament_rules', [
            'tournament_id' => $tournament->id,
            'rule_key' => 'puntos_por_set',
            'value' => '25',
        ]);
        $this->assertDatabaseHas('tournament_rules', [
            'tournament_id' => $tournament->id,
            'rule_key' => 'libero_habilitado',
            'value' => '1',
        ]);
        $this->assertSame('3', $tournament->rulesMap()['sets_para_ganar_partido']);
    }

    public function test_store_rejects_invalid_rule_values(): void
    {
        $this->actingAs($this->user);

        $this->post(route('tournaments.store'), [
            'name' => 'Copa Voley',
            'sport' => 'volleyball',
            'consoles_count' => 2,
            'teams' => [
                ['name' => 'A', 'players' => []],
                ['name' => 'B', 'players' => []],
            ],
            'rules' => [
                'puntos_por_set' => '999',
                'rotacion_obligatoria' => 'quizas',
            ],
        ])->assertSessionHasErrors();

        $this->assertDatabaseMissing('tournaments', ['name' => 'Copa Voley']);
    }

    public function test_store_defaults_mode_to_virtual(): void
    {
        $this->actingAs($this->user);

        $this->post(route('tournaments.store'), [
            'name' => 'Torneo Clasico',
            'sport' => 'fifa',
            'consoles_count' => 1,
            'players' => ['Alice', 'Bob'],
        ]);

        $this->assertSame('virtual', Tournament::where('name', 'Torneo Clasico')->first()->mode);
    }

    public function test_show_passes_tournament_rules(): void
    {
        $tournament = Tournament::factory()->for($this->user)->create([
            'name' => 'Voley Test',
            'sport' => 'volleyball',
            'mode' => 'physical',
        ]);
        $tournament->rules()->create(['rule_key' => 'puntos_por_set', 'value' => '21']);
        $tournament->rules()->create(['rule_key' => 'libero_habilitado', 'value' => '1']);

        $this->actingAs($this->user)
            ->get(route('tournaments.show', $tournament))
            ->assertInertia(fn ($page) => $page
                ->component('Tournaments/Show')
                ->where('tournamentRules.puntos_por_set', '21')
                ->has('rulesList', 2)
            );
    }

    public function test_rule_validation_respects_sport_definitions(): void
    {
        $soccer = SportRuleDefinition::where('sport', 'soccer')->where('key', 'duracion_tiempo_min')->first();
        $this->assertNotNull($soccer);
        $this->assertSame(10, $soccer->min);
        $this->assertSame(45, $soccer->max);
        $this->assertSame('number', $soccer->type);
    }
}
