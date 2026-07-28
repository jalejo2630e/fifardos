<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\StandingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeminiChatController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        $tournament = Tournament::with('prizes')->latest()->first();
        $context = $this->buildContext($tournament);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . config('services.gemini.api_key'), [
            'system_instruction' => [
                'parts' => [
                    ['text' => "Eres un asistente de la FIFARDOS ELITE LEAGUE, una liga competitiva de FIFA.
Respondes en español de forma breve y amigable. Usas la siguiente información real del torneo para responder:

{$context}

Si el usuario pregunta algo que no está en esta información, dilo amablemente y sugiérele visitar la página web o contactar al organizador."],
                ],
            ],
            'contents' => [
                [
                    'parts' => [
                        ['text' => $request->input('message')],
                    ],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => 500,
                'temperature' => 0.7,
            ],
        ]);

        $body = $response->json();

        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? 'Lo siento, no pude procesar tu mensaje.';

        return response()->json(['reply' => $text]);
    }

    private function buildContext(?Tournament $tournament): string
    {
        if (!$tournament) {
            return "No hay torneo activo actualmente. Informa que pronto habrá novedades.";
        }

        $lines = [];
        $lines[] = "=== TORNEO ACTIVO ===";
        $lines[] = "Nombre: {$tournament->name}";
        $lines[] = "Estado: {$tournament->status}";
        $lines[] = "Jugadores registrados: {$tournament->players()->count()}";
        $lines[] = "Fecha de creación: {$tournament->created_at->format('d/m/Y')}";

        if ($tournament->finished_at) {
            $lines[] = "Finalizado: {$tournament->finished_at->format('d/m/Y H:i')}";
        }

        $tournament->load('matches');
        $standings = app(StandingsService::class)->calculate($tournament);

        $prizes = $tournament->prizes->sortBy('position');
        if ($prizes->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "=== PREMIOS ===";
            foreach ($prizes as $p) {
                $perks = $p->perks ? ' (' . implode(', ', $p->perks) . ')' : '';
                $lines[] = "- {$p->position}º: {$p->label} — {$p->amount}{$perks}";
            }
        }

        if (!empty($standings)) {
            $label = $tournament->status === 'completed' ? 'TABLA FINAL' : 'TABLA DE POSICIONES';
            $lines[] = "";
            $lines[] = "=== {$label} ===";
            foreach ($standings as $i => $s) {
                $lines[] = ($i + 1) . ". {$s['player_name']} — {$s['pts']} pts ({$s['pg']}G {$s['pe']}E {$s['pp']}P, DG: {$s['dg']})";
            }
        }

        $lines[] = "";
        $lines[] = "=== REGISTRO ===";
        $lines[] = "Los jugadores pueden registrarse en la página de Registro (ruta /inscribirse).";
        $lines[] = "El torneo usa formato round-robin en fase de grupos y luego eliminatorias directas (knockout).";

        return implode("\n", $lines);
    }
}
