<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PublicPlayerController extends Controller
{
    public function create()
    {
        $tournaments = Tournament::where('status', 'setup')
            ->withCount('players')
            ->get(['id', 'name', 'max_players'])
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'max_players' => $t->max_players,
                'players_count' => $t->players_count,
                'full' => $t->max_players && $t->players_count >= $t->max_players,
            ]);

        return Inertia::render('Public/Register', [
            'tournaments' => $tournaments,
            'seo' => [
                'title' => 'Inscríbete a un torneo de fútbol | FIFARDOS',
                'description' => 'Regístrate gratis y súmate a un torneo abierto en FIFARDOS. Elige el torneo, '
                    . 'crea tu jugador y compite por la tabla, las eliminatorias y el título.',
                'type' => 'website',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'name' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'psn_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('players')->where(fn($q) => $q->where('tournament_id', $request->tournament_id)),
            ],
            'email' => 'required|email|max:255',
            'preferred_team' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed|regex:/[A-Z]/',
        ]);

        $tournament = Tournament::findOrFail($validated['tournament_id']);

        if ($tournament->status !== 'setup') {
            return back()->withErrors(['tournament_id' => 'Este torneo ya no acepta inscripciones.'])->withInput();
        }

        if ($tournament->max_players && $tournament->players()->count() >= $tournament->max_players) {
            return back()->withErrors(['tournament_id' => 'Este torneo ya alcanzó el cupo máximo de jugadores.'])->withInput();
        }

        $validated['username'] = $this->generateUsername($validated['name'], $validated['apellido'], $validated['tournament_id']);
        $validated['password'] = bcrypt($validated['password']);

        $tournament->players()->create($validated);

        return back()->with('success', 'TRANSMISSION RECEIVED');
    }

    private function generateUsername(string $nombre, string $apellido, int $tournamentId): string
    {
        $base = strtolower(trim(mb_substr($nombre, 0, 1) . $apellido));
        $base = @iconv('UTF-8', 'ASCII//TRANSLIT', $base);
        $base = preg_replace('/[^a-z0-9._-]/', '', $base);
        $base = trim($base, '._-') ?: 'player';

        $username = $base;
        $counter = 1;
        while (Tournament::find($tournamentId)?->players()->where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }
}
