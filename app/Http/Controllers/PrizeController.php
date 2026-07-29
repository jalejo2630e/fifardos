<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentPrize;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrizeController extends Controller
{
    private function ensureOwner(Tournament $tournament): void
    {
        abort_unless((int) $tournament->user_id === (int) auth()->id(), 403);
    }

    public function index(Tournament $tournament)
    {
        $this->ensureOwner($tournament);
        $tournament->load('prizes');

        return Inertia::render('Admin/PrizesManager', [
            'tournament' => $tournament,
            'prizes' => $tournament->prizes->sortBy('position')->values(),
        ]);
    }

    public function store(Request $request, Tournament $tournament)
    {
        $this->ensureOwner($tournament);
        $validated = $request->validate([
            'position' => 'required|integer|min:1',
            'label' => 'required|string|max:255',
            'amount' => 'required|string|max:255',
            'perks' => 'nullable|array',
            'icon' => 'nullable|string|max:255',
            'is_featured' => 'boolean',
        ]);

        $tournament->prizes()->create($validated);

        return redirect()->back()->with('success', 'Premio agregado.');
    }

    public function update(Request $request, Tournament $tournament, TournamentPrize $prize)
    {
        $this->ensureOwner($tournament);
        abort_unless((int) $prize->tournament_id === (int) $tournament->id, 404);
        $validated = $request->validate([
            'position' => 'required|integer|min:1',
            'label' => 'required|string|max:255',
            'amount' => 'required|string|max:255',
            'perks' => 'nullable|array',
            'icon' => 'nullable|string|max:255',
            'is_featured' => 'boolean',
        ]);

        $prize->update($validated);

        return redirect()->back()->with('success', 'Premio actualizado.');
    }

    public function destroy(Tournament $tournament, TournamentPrize $prize)
    {
        $this->ensureOwner($tournament);
        abort_unless((int) $prize->tournament_id === (int) $tournament->id, 404);
        $prize->delete();

        return redirect()->back()->with('success', 'Premio eliminado.');
    }
}
