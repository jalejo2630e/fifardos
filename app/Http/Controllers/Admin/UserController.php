<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                   ->orWhere('email', 'like', "%{$search}%");
            }))
            ->withCount('tournaments')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_admin' => (bool) $u->is_admin,
                'tournaments_count' => $u->tournaments_count,
                'created_at' => optional($u->created_at)->toDateString(),
                'is_self' => $u->id === $request->user()->id,
            ]);

        return Inertia::render('Admin/Usuarios', [
            'users' => $users,
            'filters' => ['q' => $search],
        ]);
    }

    /**
     * Alterna el rol de administrador de otro usuario.
     * Un admin no puede quitarse el rol a sí mismo (evita quedarse sin acceso).
     */
    public function toggleAdmin(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'No podés cambiar tu propio rol de administrador.');
        }

        $user->is_admin = ! $user->is_admin;
        $user->save();

        $msg = $user->is_admin
            ? "«{$user->name}» ahora es administrador."
            : "Se le quitó el rol de administrador a «{$user->name}».";

        return back()->with('success', $msg);
    }
}
