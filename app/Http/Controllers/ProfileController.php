<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $sqs = $request->user()->securityQuestions()->get();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'catalog' => \App\Helpers\SecurityQuestionCatalog::all(),
            'securityQuestions' => $sqs->pluck('question')->values()->toArray() ?: ['', '', ''],
            'hasSecuritySetup' => $sqs->count() === 3,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $previous = $user->avatar; // para borrar el archivo anterior si era subido
            $data['avatar'] = $this->storeSanitizedAvatar($request->file('avatar'));

            if ($previous && Str::startsWith($previous, 'avatars/')) {
                Storage::disk('public')->delete($previous);
            }
        } elseif (!array_key_exists('avatar', $data) || $data['avatar'] === '') {
            unset($data['avatar']);
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Reprocesa la imagen subida con GD y la guarda como PNG normalizado (máx 512px).
     * Re-encodear descarta cualquier dato que no sea la imagen (EXIF, payloads,
     * archivos "polyglot"), lo que sanitiza el avatar. La validación de Laravel
     * (image/mimes/dimensions) ya rechazó lo que no fuera imagen.
     *
     * Devuelve la ruta relativa en el disco 'public' (p. ej. "avatars/uuid.png").
     */
    private function storeSanitizedAvatar(UploadedFile $file): string
    {
        try {
            $src = @imagecreatefromstring(file_get_contents($file->getRealPath()));
            if ($src === false) {
                throw new \RuntimeException('GD no pudo decodificar la imagen');
            }

            $w = imagesx($src);
            $h = imagesy($src);
            $max = 512;
            $ratio = min(1, $max / max($w, $h));
            $nw = max(1, (int) round($w * $ratio));
            $nh = max(1, (int) round($h * $ratio));

            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);

            $relative = 'avatars/' . Str::uuid()->toString() . '.png';
            Storage::disk('public')->makeDirectory('avatars');
            imagepng($dst, Storage::disk('public')->path($relative));
            imagedestroy($dst);

            return $relative;
        } catch (\Throwable $e) {
            // Fallback: si GD no está disponible o falla, guarda el original ya
            // validado como imagen por las reglas del FormRequest.
            \Illuminate\Support\Facades\Log::warning('No se pudo reprocesar el avatar, se guarda el original', ['error' => $e->getMessage()]);
            return $file->store('avatars', 'public');
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
