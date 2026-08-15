<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Support\HumanChallenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Formulario de contacto del landing: envía correo al destinatario configurado
 * cuando el captcha (HumanChallenge) es válido, y lo bloquea cuando no.
 */
class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /** Siembra un desafío resuelto en la sesión (respuesta = 5, sin time-trap). */
    private function withSolvedChallenge(): void
    {
        $this->withSession([
            HumanChallenge::ANSWER_KEY => 5,
            HumanChallenge::TIME_KEY => now()->subSeconds(5)->timestamp,
        ]);
    }

    public function test_landing_exposes_a_captcha_challenge(): void
    {
        $this->get('/')->assertOk();
        // El desafío queda en sesión para el POST posterior.
        $this->assertNotNull(session(HumanChallenge::ANSWER_KEY));
    }

    public function test_contact_sends_email_with_valid_captcha(): void
    {
        Mail::fake();
        $this->withSolvedChallenge();

        $this->post('/contacto', [
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'message' => 'Hola, quiero proponer una alianza.',
            'captcha' => 5,
            'website' => '',
        ])->assertRedirect();

        Mail::assertSent(ContactMessageMail::class, function ($mail) {
            return $mail->hasTo(config('contact.to'))
                && $mail->senderEmail === 'ana@example.com'
                && str_contains($mail->body, 'alianza');
        });
    }

    public function test_contact_rejects_wrong_captcha(): void
    {
        Mail::fake();
        $this->withSolvedChallenge();

        $this->post('/contacto', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'spam spam spam',
            'captcha' => 99,
            'website' => '',
        ])->assertSessionHasErrors('captcha');

        Mail::assertNothingSent();
    }

    public function test_contact_honeypot_blocks_bots(): void
    {
        Mail::fake();
        $this->withSolvedChallenge();

        $this->post('/contacto', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'spam spam spam',
            'captcha' => 5,
            'website' => 'http://spam.example',
        ])->assertSessionHasErrors('captcha');

        Mail::assertNothingSent();
    }
}
