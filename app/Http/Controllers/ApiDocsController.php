<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Agent\AgentApiController;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApiDocsController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/ApiDocs', [
            'endpoints' => AgentApiController::getSchemaEndpoints(),
            'baseUrl' => url('/api/agent'),
            'tokens' => auth()->user()->tokens()->orderBy('created_at', 'desc')->get()->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'abilities' => $t->abilities,
                'created_at' => $t->created_at,
                'last_used_at' => $t->last_used_at,
            ]),
        ]);
    }
}
