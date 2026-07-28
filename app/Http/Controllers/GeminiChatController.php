<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeminiChatController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . config('services.gemini.api_key'), [
            'system_instruction' => [
                'parts' => [
                    ['text' => 'Eres un asistente de la FIFARDOS ELITE LEAGUE, una liga competitiva de FIFA.
Respondes en español de forma breve y amigable. Ayudas con información sobre:
- Cómo registrarse en el torneo
- Cómo funciona la liga (fase de grupos y eliminatorias)
- Fechas y reglas generales
- Premios del torneo

No inventes información que no conozcas. Si no sabes algo, dilo amablemente.'],
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
                'maxOutputTokens' => 300,
                'temperature' => 0.7,
            ],
        ]);

        $body = $response->json();

        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? 'Lo siento, no pude procesar tu mensaje.';

        return response()->json(['reply' => $text]);
    }
}
