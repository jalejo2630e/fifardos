<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_requires_auth(): void
    {
        $this->get(route('api-tokens.index'))->assertRedirect(route('login'));
    }

    public function test_store_requires_auth(): void
    {
        $this->post(route('api-tokens.store'), ['name' => 'test'])
            ->assertRedirect(route('login'));
    }

    public function test_destroy_requires_auth(): void
    {
        $this->delete(route('api-tokens.destroy', 1))
            ->assertRedirect(route('login'));
    }

    public function test_store_creates_token_and_returns_plaintext(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('api-tokens.store'), ['name' => 'n8n agent']);

        $response->assertRedirect();
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'n8n agent',
            'abilities' => '["agent:access"]',
        ]);
    }

    public function test_store_validates_name_required(): void
    {
        $this->actingAs($this->user)
            ->post(route('api-tokens.store'), ['name' => ''])
            ->assertSessionHasErrors(['name']);
    }

    public function test_index_lists_tokens(): void
    {
        $this->actingAs($this->user);
        $this->post(route('api-tokens.store'), ['name' => 'token uno']);
        $this->post(route('api-tokens.store'), ['name' => 'token dos']);

        $response = $this->actingAs($this->user)->get(route('api-tokens.index'));

        $response->assertOk();
        $this->assertCount(2, $response['data']);
        $this->assertEquals('token uno', $response['data'][0]['name']);
    }

    public function test_destroy_revokes_token(): void
    {
        $this->actingAs($this->user);
        $this->post(route('api-tokens.store'), ['name' => 'to revoke']);

        $tokenId = $this->user->tokens()->first()->id;

        $this->actingAs($this->user)
            ->delete(route('api-tokens.destroy', $tokenId))
            ->assertRedirect();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_destroy_cannot_revoke_other_users_token(): void
    {
        $other = User::factory()->create();
        $this->actingAs($other);
        $this->post(route('api-tokens.store'), ['name' => 'other token']);
        $tokenId = $other->tokens()->first()->id;

        $this->actingAs($this->user)
            ->delete(route('api-tokens.destroy', $tokenId))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $tokenId]);
    }
}
