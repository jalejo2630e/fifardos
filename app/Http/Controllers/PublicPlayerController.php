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
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'name' => 'required|string|max:255',
            'psn_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('players')->where(fn($q) => $q->where('tournament_id', $request->tournament_id)),
            ],
            'email' => 'required|email|max:255',
            'preferred_team' => 'required|string|max:255',
        ]);

        $tournament = Tournament::findOrFail($validated['tournament_id']);

        if ($tournament->status !== 'setup') {
            return back()->withErrors(['tournament_id' => 'Este torneo ya no acepta inscripciones.'])->withInput();
        }

        if ($tournament->max_players && $tournament->players()->count() >= $tournament->max_players) {
            return back()->withErrors(['tournament_id' => 'Este torneo ya alcanzó el cupo máximo de jugadores.'])->withInput();
        }

        $tournament->players()->create($validated);

        return back()->with('success', 'TRANSMISSION RECEIVED');
    }
}
