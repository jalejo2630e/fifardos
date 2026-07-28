<?php

namespace App\Console\Commands;

use App\Models\GameMatch;
use App\Models\Player;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateSummaries extends Command
{
    protected $signature = 'fifardos:generate-summaries {player_id?} {--month=}';

    protected $description = 'Genera resúmenes narrativos de rendimiento de jugadores usando Gemini';

    public function handle(): int
    {
        $month = $this->option('month') ?? now()->format('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->error('Formato de --month inválido. Usa YYYY-MM (ej: 2026-07).');
            return Command::FAILURE;
        }

        $periodStart = Carbon::parse($month . '-01')->startOfMonth();
        $periodEnd = Carbon::parse($month . '-01')->endOfMonth();

        $playerId = $this->argument('player_id');
        $players = $playerId
            ? Player::where('id', $playerId)->get()
            : Player::whereHas('tournament', fn($q) => $q->whereIn('status', ['in_progress', 'finished']))->get();

        if ($players->isEmpty()) {
            $this->warn($playerId ? "Jugador #{$playerId} no encontrado." : 'No hay jugadores activos.');
            return Command::SUCCESS;
        }

        $mesNombre = $periodStart->locale('es')->isoFormat('MMMM YYYY');
        $this->line("Procesando {$players->count()} jugadores para {$mesNombre}...");

        $processed = 0;
        $withSummary = 0;
        $noMatches = 0;
        $withGenerationError = 0;
        $withEmbedding = 0;
        $noEmbedding = 0;

        foreach ($players as $player) {
            $processed++;

            $this->line("  [{$processed}/{$players->count()}] {$player->name}...");

            $matchesAsP1 = GameMatch::where('player1_id', $player->id)
                ->where('status', 'finished')
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->get();

            $matchesAsP2 = GameMatch::where('player2_id', $player->id)
                ->where('status', 'finished')
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->get();

            if ($matchesAsP1->isEmpty() && $matchesAsP2->isEmpty()) {
                $this->warn("    ↪ Sin partidos en {$mesNombre}");
                $noMatches++;
                continue;
            }

            $played = 0;
            $wins = 0;
            $draws = 0;
            $losses = 0;
            $goalsFor = 0;
            $goalsAgainst = 0;

            foreach ($matchesAsP1 as $m) {
                $played++;
                $goalsFor += $m->score1;
                $goalsAgainst += $m->score2;
                if ($m->score1 > $m->score2) $wins++;
                elseif ($m->score1 === $m->score2) $draws++;
                else $losses++;
            }

            foreach ($matchesAsP2 as $m) {
                $played++;
                $goalsFor += $m->score2;
                $goalsAgainst += $m->score1;
                if ($m->score2 > $m->score1) $wins++;
                elseif ($m->score2 === $m->score1) $draws++;
                else $losses++;
            }

            $prompt = "Estos son datos reales de un torneo ya jugado. No cuestiones ni menciones la fecha. Redacta ÚNICAMENTE un resumen breve y natural en español, en tono informal de narrador deportivo, basado exclusivamente en estos datos: {$player->name} jugó {$played} partidos en {$mesNombre}, con {$wins} victorias, {$draws} empates y {$losses} derrotas, anotando {$goalsFor} goles a favor y recibiendo {$goalsAgainst} en contra. Máximo 3 oraciones. No agregues advertencias, disculpas ni comentarios sobre si el evento ya ocurrió o no — asume que sí ocurrió.";

            try {
                $response = Http::timeout(30)
                    ->withHeader('x-goog-api-key', config('services.gemini.api_key'))
                    ->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent', [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => ['maxOutputTokens' => 200, 'temperature' => 0.7],
                    ]);

                if ($response->failed()) {
                    throw new \Exception("Gemini generate error: {$response->status()} - {$response->body()}");
                }

                $summaryText = $response->json('candidates.0.content.parts.0.text');

                if (empty($summaryText)) {
                    throw new \Exception('Gemini devolvió respuesta vacía');
                }

                DB::table('match_summaries')->updateOrInsert(
                    [
                        'player_id' => $player->id,
                        'period_start' => $periodStart->toDateString(),
                        'period_end' => $periodEnd->toDateString(),
                    ],
                    [
                        'tournament_id' => null,
                        'summary_text' => $summaryText,
                        'embedding' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $this->info("    ✓ Resumen guardado");
                $withSummary++;

                try {
                    $geminiResponse = Http::timeout(15)
                        ->withHeader('x-goog-api-key', config('services.gemini.api_key'))
                        ->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent', [
                            'model' => 'models/gemini-embedding-001',
                            'content' => ['parts' => [['text' => $summaryText]]],
                            'output_dimensionality' => 768,
                        ]);

                    if ($geminiResponse->failed()) {
                        throw new \Exception("Gemini error: {$geminiResponse->status()} - {$geminiResponse->body()}");
                    }

                    $embedding = $geminiResponse->json('embedding.values');

                    if (!is_array($embedding)) {
                        throw new \Exception('Gemini no devolvió un array de embedding');
                    }

                    $embeddingJson = json_encode($embedding);

                    DB::table('match_summaries')
                        ->where('player_id', $player->id)
                        ->where('period_start', $periodStart->toDateString())
                        ->where('period_end', $periodEnd->toDateString())
                        ->update(['embedding' => $embeddingJson]);

                    $this->line("      ↪ Embedding generado (" . count($embedding) . " dimensiones)");
                    $withEmbedding++;

                } catch (\Throwable $e) {
                    $this->warn("    ↪ Embedding no generado: {$e->getMessage()}");
                    Log::warning("GenerateSummaries: embedding falló para {$player->name} (#{$player->id}): {$e->getMessage()}");
                    $noEmbedding++;
                }

            } catch (\Throwable $e) {
                $this->error("    ✗ Error: {$e->getMessage()}");
                Log::warning("GenerateSummaries (Gemini): {$player->name} (#{$player->id}): {$e->getMessage()}");
                $withGenerationError++;
            }
        }

        $this->newLine();
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("  Jugadores procesados:    {$processed}");
        $this->line("  Con resumen generado:    {$withSummary}");
        $this->line("  Con embedding:           {$withEmbedding}");
        $this->line("  Sin embedding (Gemini):  {$noEmbedding}");
        $this->line("  Sin partidos:            {$noMatches}");
        $this->line("  Con error (generación):  {$withGenerationError}");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        return Command::SUCCESS;
    }
}
