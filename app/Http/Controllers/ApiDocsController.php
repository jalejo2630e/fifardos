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
        ]);
    }
}
