<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentPrize;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrizeController extends Controller
{
    public function index(Tournament $tournament)
    {
        $tournament->load('prizes');

        return Inertia::render('Admin/PrizesManager', [
            'tournament' => $tournament,
            'prizes' => $tournament->prizes->sortBy('position')->values(),
        ]);
    }

    public function store(Request $request, Tournament $tournament)
    {
        $validated = $request->validate([
            'position' => 'required|integer|min:1',
            'label' => 'required|string|max:255',
            'amount' => 'required|string|max:255',
            'perks' => 'nullable|array',
            'icon' => 'nullable|string|max:255',
            'is_featured' => 'boolean',
        ]);

        $tournament->prizes()->create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Tournament $tournament, TournamentPrize $prize)
    {
        $validated = $request->validate([
            'position' => 'required|integer|min:1',
            'label' => 'required|string|max:255',
            'amount' => 'required|string|max:255',
            'perks' => 'nullable|array',
            'icon' => 'nullable|string|max:255',
            'is_featured' => 'boolean',
        ]);

        $prize->update($validated);

        return redirect()->back();
    }

    public function destroy(Tournament $tournament, TournamentPrize $prize)
    {
        $prize->delete();

        return redirect()->back();
    }
}
