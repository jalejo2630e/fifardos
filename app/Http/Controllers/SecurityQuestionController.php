<?php

namespace App\Http\Controllers;

use App\Helpers\SecurityQuestionCatalog;
use App\Models\User;
use App\Models\UserSecurityQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SecurityQuestionController extends Controller
{
    public function catalog()
    {
        return response()->json(['questions' => SecurityQuestionCatalog::all()]);
    }

    public function setupForm(Request $request)
    {
        $existing = $request->user()->securityQuestions()->count();
        if ($existing >= 3) {
            return redirect()->route('dashboard')->with('status', 'Ya tienes preguntas de seguridad configuradas.');
        }

        return Inertia::render('Auth/SetupSecurityQuestions', [
            'catalog' => SecurityQuestionCatalog::all(),
            'is_required' => $existing === 0,
        ]);
    }

    public function setupStore(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'questions' => 'required|array|size:3',
            'questions.*.question' => 'required|string',
            'questions.*.answer' => 'required|string|min:2|max:100',
        ]);

        $existing = $user->securityQuestions()->count();
        if ($existing >= 3) {
            return back()->withErrors(['questions' => 'Ya tienes 3 preguntas configuradas.']);
        }

        foreach ($request->questions as $q) {
            if (!SecurityQuestionCatalog::isValid($q['question'])) {
                return back()->withErrors(['questions' => 'Una o más preguntas no están en el catálogo permitido.']);
            }
        }

        $user->securityQuestions()->delete();

        foreach ($request->questions as $q) {
            UserSecurityQuestion::create([
                'user_id' => $user->id,
                'question' => $q['question'],
                'answer_hash' => SecurityQuestionCatalog::hashAnswer($q['answer']),
            ]);
        }

        return redirect()->route('dashboard')->with('status', 'Preguntas de seguridad configuradas correctamente.');
    }

    public function showRecoverForm()
    {
        return Inertia::render('Auth/ForgotPasswordQuestions', [
            'step' => 'email',
            'questions' => [],
            'has_setup' => false,
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $key = 'recover-questions:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, SecurityQuestionCatalog::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => "Demasiados intentos. Intenta de nuevo en " . ceil($seconds / 60) . " minutos.",
            ]);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            RateLimiter::hit($key, SecurityQuestionCatalog::DECAY_MINUTES * 60);
            Log::warning('Recuperación: email no encontrado', ['email' => $request->email, 'ip' => $request->ip()]);
            return back()->withErrors(['email' => 'No encontramos una cuenta con ese correo.']);
        }

        $sqs = $user->securityQuestions()->get();
        if ($sqs->count() !== 3) {
            RateLimiter::hit($key, SecurityQuestionCatalog::DECAY_MINUTES * 60);
            Log::warning('Recuperación: preguntas no configuradas', ['user_id' => $user->id, 'ip' => $request->ip()]);
            return back()->withErrors(['email' => 'Este usuario no tiene preguntas de seguridad configuradas.']);
        }

        return Inertia::render('Auth/ForgotPasswordQuestions', [
            'step' => 'questions',
            'user_email' => $request->email,
            'questions' => $sqs->pluck('question')->values()->toArray(),
            'has_setup' => true,
        ]);
    }

    public function verifyAnswers(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'answers' => 'required|array|size:3',
            'answers.*' => 'required|string|min:1|max:100',
        ]);

        $key = 'recover-answers:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, SecurityQuestionCatalog::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'answers' => "Demasiados intentos. Intenta de nuevo en " . ceil($seconds / 60) . " minutos.",
            ]);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'No encontramos una cuenta con ese correo.']);
        }

        $sqs = $user->securityQuestions()->get();
        if ($sqs->count() !== 3) {
            return back()->withErrors(['email' => 'Preguntas de seguridad no configuradas.']);
        }

        $allCorrect = true;
        foreach ($sqs as $i => $sq) {
            $normalized = SecurityQuestionCatalog::normalizeAnswer($request->answers[$i]);
            if (!SecurityQuestionCatalog::checkAnswer($request->answers[$i], $sq->answer_hash)) {
                $allCorrect = false;
                break;
            }
        }

        if (!$allCorrect) {
            RateLimiter::hit($key, SecurityQuestionCatalog::DECAY_MINUTES * 60);
            Log::warning('Recuperación: respuestas incorrectas', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);
            return back()->withErrors(['answers' => 'Una o más respuestas son incorrectas.']);
        }

        RateLimiter::clear($key);

        $token = Str::random(60);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['email' => $request->email, 'token' => Hash::make($token), 'created_at' => now()]
        );

        Log::info('Recuperación exitosa vía preguntas de seguridad', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return Inertia::render('Auth/ForgotPasswordQuestions', [
            'step' => 'reset',
            'user_email' => $request->email,
            'user_name' => $user->name,
            'token' => $token,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->withErrors(['token' => 'Token inválido o expirado. Intenta de nuevo.']);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['token' => 'El enlace ha expirado. Intenta de nuevo.']);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'Usuario no encontrado.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('status', 'Contraseña restablecida correctamente.');
    }

    public function profileForm(Request $request)
    {
        $sqs = $request->user()->securityQuestions()->get();
        $existingQuestions = $sqs->pluck('question')->values()->toArray();

        return Inertia::render('Profile/Partials/SecurityQuestionsForm', [
            'catalog' => SecurityQuestionCatalog::all(),
            'existing_questions' => $existingQuestions,
            'has_setup' => $sqs->count() === 3,
        ]);
    }

    public function profileUpdate(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'questions' => 'required|array|size:3',
            'questions.*.question' => 'required|string',
            'questions.*.answer' => 'required|string|min:2|max:100',
        ]);

        $user = $request->user();

        foreach ($request->questions as $q) {
            if (!SecurityQuestionCatalog::isValid($q['question'])) {
                return back()->withErrors(['questions' => 'Una o más preguntas no están en el catálogo permitido.']);
            }
        }

        $user->securityQuestions()->delete();

        foreach ($request->questions as $q) {
            UserSecurityQuestion::create([
                'user_id' => $user->id,
                'question' => $q['question'],
                'answer_hash' => SecurityQuestionCatalog::hashAnswer($q['answer']),
            ]);
        }

        return redirect()->route('profile.edit')->with('status', 'Preguntas de seguridad actualizadas correctamente.');
    }
}
