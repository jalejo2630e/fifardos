<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'captcha' => \App\Support\HumanChallenge::make(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        $rules['avatar'] = $request->hasFile('avatar')
            ? 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048'
            : ['nullable', 'string', 'max:16', 'not_regex:/[\/:<>]/'];

        $request->validate($rules);

        $avatar = $request->input('avatar');

        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'avatar' => $avatar,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('security-questions.setup.form', absolute: false));
    }
}
