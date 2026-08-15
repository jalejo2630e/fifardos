<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Valida el soporte OAuth 2.1 del endpoint MCP remoto para conectores de UI web
 * (Claude.ai, ChatGPT): discovery (RFC 8414/9728), registro dinámico (RFC 7591)
 * y el flujo completo Authorization Code + PKCE hasta llamar /mcp con el token.
 */
class McpOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorization_server_metadata_is_published(): void
    {
        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('response_types_supported.0', 'code')
            ->assertJsonPath('code_challenge_methods_supported.0', 'S256')
            ->assertJsonFragment(['grant_types_supported' => ['authorization_code', 'refresh_token']])
            ->assertJsonStructure(['issuer', 'authorization_endpoint', 'token_endpoint', 'registration_endpoint']);
    }

    public function test_protected_resource_metadata_points_to_the_authorization_server(): void
    {
        $issuer = rtrim(url('/'), '/');

        $this->getJson('/.well-known/oauth-protected-resource')
            ->assertOk()
            ->assertJsonPath('resource', $issuer.'/mcp')
            ->assertJsonPath('authorization_servers.0', $issuer);

        // También en la variante con el path del recurso.
        $this->getJson('/.well-known/oauth-protected-resource/mcp')->assertOk();
    }

    public function test_unauthenticated_mcp_returns_401_with_www_authenticate(): void
    {
        $response = $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);

        $response->assertStatus(401);
        $this->assertStringContainsString(
            'resource_metadata=',
            (string) $response->headers->get('WWW-Authenticate')
        );
    }

    public function test_dynamic_client_registration_creates_a_public_client(): void
    {
        $body = $this->postJson('/oauth/register', [
            'client_name' => 'Claude Test',
            'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
            'token_endpoint_auth_method' => 'none',
        ])->assertCreated()->json();

        $this->assertNotEmpty($body['client_id']);
        $this->assertSame('none', $body['token_endpoint_auth_method']);
        $this->assertArrayNotHasKey('client_secret', $body);
        $this->assertContains('authorization_code', $body['grant_types']);
    }

    public function test_full_web_connector_flow_authorization_code_pkce(): void
    {
        $user = User::factory()->create();
        Tournament::factory()->for($user)->create(['name' => 'Copa OAuth']);

        $redirect = 'https://claude.ai/api/mcp/auth_callback';

        // 1) Registro dinámico → client_id.
        $clientId = $this->postJson('/oauth/register', [
            'client_name' => 'Claude',
            'redirect_uris' => [$redirect],
            'token_endpoint_auth_method' => 'none',
        ])->assertCreated()->json('client_id');

        // 2) PKCE: verifier + challenge S256.
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $state = Str::random(16);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirect,
            'response_type' => 'code',
            'scope' => 'mcp',
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]);

        // 3) El usuario, logueado, ve el consentimiento y lo aprueba.
        $this->actingAs($user);
        $consent = $this->get('/oauth/authorize?'.$query)->assertOk();

        // El auth_token viaja en la pantalla de consentimiento (campo oculto).
        preg_match('/name="auth_token" value="([^"]+)"/', $consent->getContent(), $m);
        $authToken = $m[1] ?? null;
        $this->assertNotEmpty($authToken);

        $approve = $this->post('/oauth/authorize', ['auth_token' => $authToken, 'state' => $state]);
        $approve->assertRedirect();

        parse_str((string) parse_url($approve->headers->get('Location'), PHP_URL_QUERY), $cb);
        $this->assertSame($state, $cb['state'] ?? null);
        $code = $cb['code'] ?? null;
        $this->assertNotEmpty($code);

        // 4) Canje del code por access token (público, con PKCE, sin secreto).
        $token = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'redirect_uri' => $redirect,
            'code_verifier' => $verifier,
            'code' => $code,
        ])->assertOk()->json();

        $access = $token['access_token'] ?? null;
        $this->assertNotEmpty($access);

        // 5) El access token OAuth autentica en /mcp y resuelve al usuario dueño:
        //    list_tournaments debe devolver su torneo "Copa OAuth".
        $mcp = $this->withHeader('Authorization', 'Bearer '.$access)
            ->postJson('/mcp', [
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'tools/call',
                'params' => ['name' => 'list_tournaments', 'arguments' => []],
            ]);

        $mcp->assertOk();
        $this->assertFalse($mcp->json('result.isError'));
        $this->assertStringContainsString('Copa OAuth', $mcp->json('result.content.0.text'));
    }
}
