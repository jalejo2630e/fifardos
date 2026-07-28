<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatConfigController extends Controller
{
    public function edit()
    {
        $config = ChatConfig::firstOrCreate(
            ['id' => 1],
            [
                'system_prompt' => 'Eres un asistente amigable que responde en español.',
                'forbidden_topics' => null,
                'is_active' => true,
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]
        );

        return Inertia::render('Admin/ChatConfig', [
            'config' => $config,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'system_prompt' => 'required|string|max:5000',
            'forbidden_topics' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
            'max_tokens' => 'required|integer|min:100|max:2000',
            'temperature' => 'required|numeric|min:0|max:2',
        ]);

        $config = ChatConfig::firstOrCreate(['id' => 1]);
        $config->update($validated);

        return redirect()->back()->with('success', 'Configuración del chat guardada.');
    }
}
