<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Support\HumanChallenge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Formulario de contacto del landing. El captcha anti-bots (HumanChallenge) lo
 * verifica el middleware 'human' antes de llegar aquí. El destinatario se define
 * en config/contact.php (env CONTACT_MAIL_TO).
 */
class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'message' => 'required|string|min:5|max:4000',
        ]);

        Mail::to(config('contact.to'))->send(new ContactMessageMail(
            senderName: $data['name'],
            senderEmail: $data['email'],
            body: $data['message'],
        ));

        // Consume el desafío: la recarga del landing generará uno nuevo.
        HumanChallenge::forget();

        return back();
    }
}
