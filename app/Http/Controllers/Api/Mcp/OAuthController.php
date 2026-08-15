<?php

namespace App\Http\Controllers\Api\Mcp;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passport\ClientRepository;

/**
 * Soporte OAuth 2.1 para que los conectores de UI web (Claude.ai, ChatGPT)
 * puedan conectarse al endpoint MCP remoto (/mcp) sin un token pre-generado.
 *
 * Passport ya provee /oauth/authorize y /oauth/token (Authorization Code +
 * PKCE). Aquí añadimos lo que el flujo MCP exige además y Passport no trae:
 *
 *   - Authorization Server Metadata   (RFC 8414)  /.well-known/oauth-authorization-server
 *   - Protected Resource Metadata     (RFC 9728)  /.well-known/oauth-protected-resource
 *   - Dynamic Client Registration     (RFC 7591)  POST /oauth/register
 *
 * El token Bearer de Sanctum (Agent API) sigue funcionando en paralelo: /mcp
 * acepta el guard 'sanctum,api'.
 */
class OAuthController extends Controller
{
    /** URL base (issuer) sin barra final: p. ej. https://fifardos.com */
    private function issuer(): string
    {
        return rtrim(url('/'), '/');
    }

    /**
     * RFC 8414 — metadatos del servidor de autorización.
     * El cliente MCP lo lee para descubrir dónde autorizar, pedir el token y
     * registrarse dinámicamente.
     */
    public function authorizationServer(): JsonResponse
    {
        $issuer = $this->issuer();

        return response()->json([
            'issuer' => $issuer,
            'authorization_endpoint' => $issuer.'/oauth/authorize',
            'token_endpoint' => $issuer.'/oauth/token',
            'registration_endpoint' => $issuer.'/oauth/register',
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_basic', 'client_secret_post'],
            'code_challenge_methods_supported' => ['S256'],
            'scopes_supported' => ['mcp'],
        ]);
    }

    /**
     * RFC 9728 — metadatos del recurso protegido (el propio /mcp) apuntando a
     * su servidor de autorización. Se sirve tanto en la raíz well-known como en
     * la variante con el path del recurso (/.well-known/oauth-protected-resource/mcp).
     */
    public function protectedResource(): JsonResponse
    {
        $issuer = $this->issuer();

        return response()->json([
            'resource' => $issuer.'/mcp',
            'authorization_servers' => [$issuer],
            'bearer_methods_supported' => ['header'],
            'scopes_supported' => ['mcp'],
        ]);
    }

    /**
     * RFC 7591 — registro dinámico de clientes. Los conectores MCP no tienen un
     * client_id pre-acordado: se registran aquí enviando sus redirect_uris y
     * reciben un client_id. Creamos un cliente público (PKCE, sin secreto) salvo
     * que pidan explícitamente autenticación por secreto.
     */
    public function register(Request $request, ClientRepository $clients): JsonResponse
    {
        $data = $request->validate([
            'redirect_uris' => 'required|array|min:1',
            'redirect_uris.*' => 'required|url',
            'client_name' => 'nullable|string|max:255',
            'token_endpoint_auth_method' => 'nullable|string|in:none,client_secret_basic,client_secret_post',
            'grant_types' => 'nullable|array',
            'response_types' => 'nullable|array',
            'scope' => 'nullable|string',
        ]);

        $confidential = ($data['token_endpoint_auth_method'] ?? 'none') !== 'none';

        $client = $clients->createAuthorizationCodeGrantClient(
            $data['client_name'] ?? 'MCP Client',
            $data['redirect_uris'],
            $confidential,
        );

        $body = [
            'client_id' => $client->getKey(),
            'client_name' => $client->name,
            'redirect_uris' => $client->redirect_uris,
            'grant_types' => $client->grant_types,
            'response_types' => ['code'],
            'token_endpoint_auth_method' => $confidential ? 'client_secret_basic' : 'none',
            'scope' => 'mcp',
        ];

        // Solo los clientes confidenciales reciben secreto (una única vez).
        if ($confidential && $client->plainSecret) {
            $body['client_secret'] = $client->plainSecret;
        }

        return response()->json($body, 201);
    }
}
