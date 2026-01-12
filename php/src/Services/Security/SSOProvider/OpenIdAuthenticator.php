<?php

namespace Kiniauth\Services\Security\SSOProvider;

use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\EncryptionService;
use Kiniauth\Services\Security\JWT\JWTManager;
use Kiniauth\ValueObjects\Security\SSO\OpenIdAuthenticatorConfiguration;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;

class OpenIdAuthenticator {

    /**
     * @var HttpRequestDispatcher
     */
    private HttpRequestDispatcher $requestDispatcher;

    /**
     * @var Session
     */
    private Session $session;

    /**
     * @var OpenIdAuthenticatorConfiguration
     */
    private OpenIdAuthenticatorConfiguration $config;

    /**
     * @var JWTManager
     */
    private JWTManager $jwtManager;

    /**
     * @var EncryptionService
     */
    private EncryptionService $encryptionService;

    private array $settings;

    /**
     * @param HttpRequestDispatcher $requestDispatcher
     * @param Session $session
     * @param OpenIdAuthenticatorConfiguration $config
     * @param JWTManager $jwtManager
     * @param EncryptionService $encryptionService
     */
    public function __construct(HttpRequestDispatcher $requestDispatcher, Session $session, OpenIdAuthenticatorConfiguration $config, JWTManager $jwtManager, EncryptionService $encryptionService) {
        $this->requestDispatcher = $requestDispatcher;
        $this->session = $session;
        $this->config = $config;
        $this->jwtManager = $jwtManager;
        $this->encryptionService = $encryptionService;
    }

    public function initialise() {

        if (!empty($this->config->getAuthorisationEndpoint())) {
            $state = bin2hex(random_bytes(16));
            $nonce = bin2hex(random_bytes(16));

            // Store them in session
            $this->session->setValue("oidc_state", $state);
            $this->session->setValue("oidc_nonce", $nonce);

            $params = [
                'client_id' => $this->config->getClientId(),
                'redirect_uri' => $this->config->getRedirectUri(),
                'response_type' => 'code',
                'scope' => 'email',
                'state' => $state,
                'nonce' => $nonce,
            ];

            $url = $this->config->getAuthorisationEndpoint() . '?' . http_build_query($params);

            return $url;
        }

        return null;
    }

    public function authenticate(mixed $data) {

        [$code, $state] = $data;

        // 1. Validate the state
        $expectedState = $this->session->getValue("oidc_state");
        if ($state != $expectedState) {
            throw new AccessDeniedException("Invalid state");
        }

        // 2. Exchange the Authorization Code for Tokens
        [$idToken, $accessToken] = $this->requestTokens($code);

        // 3. Validate the ID Token and get claims
        $claims = $this->validateIdToken($idToken);

        return $claims["email"];

    }

    private function requestTokens(string $code): array {
        $masterKey = Configuration::readParameter("sso.oidc.masterKey");
        $clientSecret = $this->encryptionService->decrypt($masterKey, $this->config->getClientSecret());

        $params = [
            "grant_type" => "authorization_code",
            "code" => $code,
            "redirect_uri" => $this->config->getRedirectUri(),
            "client_id" => $this->config->getClientId(),
            "client_secret" => $clientSecret
        ];

        $request = new Request(
            $this->config->getTokenEndpoint(),
            Request::METHOD_POST,
            $params,
            null,
            new Headers(["Content-Type" => "application/x-www-form-urlencoded"])
        );

        $response = $this->requestDispatcher->dispatch($request);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Token request failed");
        }

        $body = json_decode($response->getBody(), true);
        $accessToken = $body["access_token"] ?? null;
        $idToken = $body["id_token"] ?? null;

        return [$idToken, $accessToken];
    }

    private function validateIdToken(string $idToken): array {
        // Validate the token and algorithm - this ensures algorithm is supported and correct format
        $alg = $this->jwtManager->validateToken($idToken);

        // Decode token through JWT library and return claims
        $claims = $this->jwtManager->decodeToken($idToken, $alg, $this->config);

        // Ensure that the claims returned match the expected provider and formats.
        if ($claims) {
            $this->jwtManager->validateClaims($claims, $this->config);
        }

        // If nonce doesn't match, refuse access
        $nonceClaim = $claims["nonce"];
        $expectedNonce = $this->session->getValue("oidc_nonce");
        if ($nonceClaim != $expectedNonce)
            throw new AccessDeniedException("Nonce mismatch");

        return $claims;
    }

}