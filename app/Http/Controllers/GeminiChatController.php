<?php

namespace App\Http\Controllers;

use App\Models\ChatConfig;
use App\Models\Tournament;
use App\Services\StandingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiChatController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        $config = ChatConfig::first();

        if ($config && !$config->is_active) {
            return response()->json(['reply' => 'El asistente está desactivado actualmente.']);
        }

        $context = $this->buildFullContext();

        $prompt = $config->system_prompt ?? 'Eres un asistente amigable que responde en español.';
        $forbidden = $config->forbidden_topics
            ? "\n\nTEMAS PROHIBIDOS (no debes hablar de esto bajo ningún concepto): {$config->forbidden_topics}"
            : '';

        $system = "{$prompt}{$forbidden}\n\n=== DATOS DE LA PLATAFORMA ===\n{$context}\n\nSiempre responde en español. Si no encuentras la respuesta en los datos proporcionados, indícalo amablemente.";

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $system]],
            ],
            'contents' => [
                ['parts' => [['text' => $request->input('message')]]],
            ],
            'generationConfig' => [
                'maxOutputTokens' => $config->max_tokens ?? 500,
                'temperature' => $config->temperature ?? 0.7,
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . config('services.gemini.api_key'),
            $payload
        );

        $body = $response->json();

        if (isset($body['error'])) {
            Log::error('Gemini API error', ['error' => $body['error']]);
            return response()->json(['reply' => 'Lo siento, ocurrió un error al procesar tu mensaje.']);
        }

        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$text) {
            Log::warning('Gemini unexpected response', ['body' => $body]);
            return response()->json(['reply' => 'Lo siento, no pude procesar tu mensaje.']);
        }

        return response()->json(['reply' => $text]);
    }

    private function buildFullContext(): string
    {
        $tournaments = Tournament::with('prizes')->latest()->get();

        if ($tournaments->isEmpty()) {
            return "No hay torneos registrados en la plataforma.";
        }

        $lines = [];
        $lines[] = "Total de torneos: {$tournaments->count()}";

        foreach ($tournaments as $t) {
            $t->load('matches');
            $standings = app(StandingsService::class)->calculate($t);

            $lines[] = "";
            $lines[] = str_repeat("=", 50);
            $lines[] = "TORNEO: {$t->name}";
            $lines[] = "Estado: {$t->status} | Jugadores: {$t->players()->count()} | Creado: {$t->created_at->format('d/m/Y')}";
            if ($t->finished_at) {
                $lines[] = "Finalizado: {$t->finished_at->format('d/m/Y H:i')}";
            }

            $prizes = $t->prizes->sortBy('position');
            if ($prizes->isNotEmpty()) {
                $lines[] = "--- PREMIOS ---";
                foreach ($prizes as $p) {
                    $perks = $p->perks ? ' (' . implode(', ', $p->perks) . ')' : '';
                    $lines[] = "  {$p->position}º: {$p->label} — {$p->amount}{$perks}";
                }
            }

            if (!empty($standings)) {
                $lines[] = "--- TABLA DE POSICIONES ---";
                foreach ($standings as $i => $s) {
                    $lines[] = "  " . ($i + 1) . ". {$s['player_name']} — {$s['pts']} pts | PJ:{$s['pj']} G:{$s['pg']} E:{$s['pe']} P:{$s['pp']} | GF:{$s['gf']} GC:{$s['gc']} DG:{$s['dg']}";
                }

                $maxGf = max(array_column($standings, 'gf'));
                if ($maxGf > 0) {
                    $topScorers = array_filter($standings, fn($s) => $s['gf'] === $maxGf);
                    $lines[] = "--- GOLEADOR(ES) ---";
                    foreach ($topScorers as $s) {
                        $lines[] = "  {$s['player_name']} — {$s['gf']} goles";
                    }
                }
            }

            $lines[] = str_repeat("=", 50);
        }

        $lines[] = "";
        $lines[] = "--- INFORMACIÓN GENERAL ---";
        $lines[] = "Registro: los jugadores se registran en la página /inscribirse";
        $lines[] = "Formato: fase de grupos (round-robin) + eliminatorias directas (knockout)";
        $lines[] = "Cada torneo es creado por un usuario administrador.";
        $lines[] = "Los partidos pueden tener resultado (score1-score2) y estado: pending / finished.";

        return implode("\n", $lines);
    }
}
