<?php

namespace Tests\Feature\Auth;

use App\Support\HumanChallenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->withSession([
            HumanChallenge::ANSWER_KEY => 7,
            HumanChallenge::TIME_KEY => now()->timestamp - 10,
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Str0ng!Passw0rd',
            'password_confirmation' => 'Str0ng!Passw0rd',
            'captcha' => 7,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('security-questions.setup.form', absolute: false));
    }
}
