<?php

namespace Tests\Feature;

use App\Models\FamilyRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dificultad de la trivia en los minijuegos: el anfitrión elige facil/normal/
 * dificil y el pool de preguntas se filtra en consecuencia (adultos = sin las
 * triviales).
 */
class TriviaDifficultyTest extends TestCase
{
    use RefreshDatabase;

    /** Textos de preguntas por nivel, leídos del config. */
    private function questionsByLevel(array $levels): array
    {
        return array_map(
            fn ($q) => $q['q'],
            array_filter(
                config('familia.trivia.questions'),
                fn ($q) => in_array($q['d'] ?? 'easy', $levels, true)
            )
        );
    }

    private function createRoom(string $hostToken = 'host-token'): string
    {
        return $this->postJson('/minijuegos', [
            'name' => 'Host',
            'token' => $hostToken,
            'game' => 'trivia',
        ])->assertOk()->json('code');
    }

    private function joinGuest(string $code, string $token = 'guest-token'): void
    {
        $this->postJson("/minijuegos/{$code}/join", ['name' => 'Guest', 'token' => $token])->assertOk();
    }

    public function test_new_room_defaults_to_easy(): void
    {
        $code = $this->createRoom();
        $this->assertSame('facil', FamilyRoom::where('code', $code)->first()->trivia_difficulty);
    }

    public function test_host_can_set_difficulty(): void
    {
        $code = $this->createRoom();

        $this->postJson("/minijuegos/{$code}/difficulty", [
            'token' => 'host-token',
            'difficulty' => 'dificil',
        ])->assertOk();

        $this->assertSame('dificil', FamilyRoom::where('code', $code)->first()->trivia_difficulty);
    }

    public function test_non_host_cannot_set_difficulty(): void
    {
        $code = $this->createRoom();
        $this->joinGuest($code);

        $this->postJson("/minijuegos/{$code}/difficulty", [
            'token' => 'guest-token',
            'difficulty' => 'dificil',
        ])->assertStatus(403);
    }

    public function test_invalid_difficulty_is_rejected(): void
    {
        $code = $this->createRoom();

        $this->postJson("/minijuegos/{$code}/difficulty", [
            'token' => 'host-token',
            'difficulty' => 'imposible',
        ])->assertStatus(422);
    }

    public function test_dificil_serves_only_medium_or_hard_questions(): void
    {
        $code = $this->createRoom();
        $this->joinGuest($code);
        $this->postJson("/minijuegos/{$code}/difficulty", ['token' => 'host-token', 'difficulty' => 'dificil'])->assertOk();
        $this->postJson("/minijuegos/{$code}/start", ['token' => 'host-token', 'games' => ['trivia']])->assertOk();

        $question = FamilyRoom::where('code', $code)->first()->state['question'];

        $this->assertContains($question, $this->questionsByLevel(['medium', 'hard']));
        $this->assertNotContains($question, $this->questionsByLevel(['easy']));
    }

    public function test_facil_serves_only_easy_questions(): void
    {
        $code = $this->createRoom();
        $this->joinGuest($code);
        // 'facil' es el default; arrancamos directamente.
        $this->postJson("/minijuegos/{$code}/start", ['token' => 'host-token', 'games' => ['trivia']])->assertOk();

        $question = FamilyRoom::where('code', $code)->first()->state['question'];

        $this->assertContains($question, $this->questionsByLevel(['easy']));
        $this->assertNotContains($question, $this->questionsByLevel(['medium', 'hard']));
    }
}
