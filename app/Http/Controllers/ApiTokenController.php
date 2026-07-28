<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiTokenController extends Controller
{
    public function index(Request $request)
    {
        $tokens = $request->user()->tokens()->orderBy('created_at', 'desc')->get()->map(fn($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'abilities' => $t->abilities,
            'created_at' => $t->created_at,
            'last_used_at' => $t->last_used_at,
        ]);

        return response()->json(['data' => $tokens]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $token = $request->user()->createToken($request->name, ['agent:access']);

        Log::info('Token API generado', [
            'user_id' => $request->user()->id,
            'token_name' => $request->name,
            'token_id' => $token->accessToken->id,
        ]);

        session()->flash('token', $token->plainTextToken);

        return redirect()->back();
    }

    public function destroy(Request $request, $tokenId)
    {
        $token = $request->user()->tokens()->where('id', $tokenId)->first();

        if (!$token) {
            return redirect()->back()->with('error', 'Token no encontrado');
        }

        Log::info('Token API revocado', [
            'user_id' => $request->user()->id,
            'token_name' => $token->name,
            'token_id' => $token->id,
        ]);

        $token->delete();

        return redirect()->back();
    }
}
