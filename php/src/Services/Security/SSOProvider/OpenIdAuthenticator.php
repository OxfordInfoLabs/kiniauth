<?php

namespace Kiniauth\Services\Security\SSOProvider;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\JWT\JWTManager;
use Kiniauth\ValueObjects\Security\SSO\OpenIdAuthenticatorConfiguration;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
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
     * @param HttpRequestDispatcher $requestDispatcher
     * @param Session $session
     * @param OpenIdAuthenticatorConfiguration $config
     * @param JWTManager $jwtManager
     */
    public function __construct(HttpRequestDispatcher $requestDispatcher, Session $session, OpenIdAuthenticatorConfiguration $config, JWTManager $jwtManager) {
        $this->requestDispatcher = $requestDispatcher;
        $this->session = $session;
        $this->config = $config;
        $this->jwtManager = $jwtManager;
    }

    public function initialise() {

        if (isset($accountSettings["openId"])) {
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

            $url = $accountSettings["openId"]["authorizationEndpoint"] . '?' . http_build_query($params);

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

        $params = [
            "grant_type" => "authorization_code",
            "code" => $code,
            "redirect_uri" => $this->config->getRedirectUri()
        ];

        $request = new Request(
            $this->config->getTokenEndpoint(),
            Request::METHOD_POST,
            $params,
            null,
            ["Content-Type" => "application/x-www-form-urlencoded"]
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

        if (!$this->jwtManager->validateToken($idToken)) {
            throw new AccessDeniedException("Invalid Token");
        }

        $claims = $this->jwtManager->decodeToken($idToken);

        $issuer = $claims["iss"];
        $audience = $claims["aud"];
        $expiry = $claims["exp"];
        $nonceClaim = $claims["nonce"];
        $azp = $claims["azp"] ?? null;


        // Follow verification as per spec
        // 2. Issuer Identifier matches iss
        if ($issuer != $this->config->getIssuer()) {
            throw new AccessDeniedException("Invalid issuer");
        }

        // 3. Audience contains it's client_id registered with Issuer
        $audienceArray = is_array($audience) ? $audience : [$audience];
        if (!in_array($this->config->getClientId(), $audienceArray)) {
            throw new AccessDeniedException("Client ID not in audience.");
        }

        // Check authorised parties (if present)
        if (is_array($audience) && count($audience) > 1 && $azp != $this->config->getClientId()) {
            throw new AccessDeniedException("Invalid authorized party (azp).");
        }

        // 9. Check token not expired (with 30s leeway)
        if ($expiry < (time() - 30))
            throw new AccessDeniedException("Token expired");

        // 11. If nonce doesn't match, refuse access
        $expectedNonce = $this->session->getValue("oidc_nonce");
        if ($nonceClaim != $expectedNonce)
            throw new AccessDeniedException("Nonce mismatch");

        return $claims;

    }

}