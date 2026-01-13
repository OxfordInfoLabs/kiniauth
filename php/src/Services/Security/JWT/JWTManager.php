<?php

namespace Kiniauth\Services\Security\JWT;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Kiniauth\ValueObjects\Security\SSO\OpenIdAuthenticatorConfiguration;
use Kinikit\Core\Logging\Logger;

/**
 * Currently only supports the HS algorithm class
 */
class JWTManager {

    private array $supportedAlgs;

    /**
     * Set the supported algorithms
     */
    public function __construct() {
        $this->supportedAlgs = array_keys(JWT::$supported_algs);
    }

    public function validateToken(string $idToken) {
        $tokenParts = explode('.', $idToken);
        if (count($tokenParts) != 3) {
            throw new \Exception("Invalid Token structure");
        }
        $header = json_decode(base64_decode($tokenParts[0]));
        $alg = $header->alg;

        if (!in_array($alg, $this->supportedAlgs)) {
            throw new \Exception("Unsupported algorithm: $alg");
        }

        return $alg;
    }

    /**
     * @param string $idToken
     * @param string $alg
     * @param OpenIdAuthenticatorConfiguration $config
     * @return \stdClass|null
     * @throws \Exception
     */
    public function decodeToken(string $idToken, string $alg, OpenIdAuthenticatorConfiguration $config): \stdClass|null {
        $claims = null;

        // Fetch the correct key based on the algorithm type
        if (str_starts_with($alg, 'RS') || str_starts_with($alg, 'ES')) {
            // Asymmetric: Fetch from JWKS (URL)
            $jwksUri = $config->getJwksUri();
            $jwks = json_decode(file_get_contents($jwksUri), true);
            $keys = JWK::parseKeySet($jwks);
            // Pass the entire array of keys; the library finds the one matching the 'kid'
            $claims = JWT::decode($idToken, $keys);
        }

        if (str_starts_with($alg, 'HS')) {
            // Symmetric: Use the Client Secret
            $key = new Key($config->getClientSecret(), $alg);
            $claims = JWT::decode($idToken, $key);
        }

        return $claims;
    }

    /**
     * Check that the claims returned are valid
     *
     * @param $claims
     * @param $config OpenIdAuthenticatorConfiguration
     * @return void
     * @throws \Exception
     */
    public function validateClaims($claims, OpenIdAuthenticatorConfiguration $config): void {
        $now = time();

        // Validate Audience (Is this the expected provider)
        if ($claims->aud !== $config->getClientId()) {
            throw new \Exception("Audience mismatch: Expected {$config->getClientId()}");
        }

        // Validate Issuer (Did this actually come from the provider I expect?)
        $expectedIssuers = [$config->getIssuer(), str_replace('https://', '', $config->getIssuer())];
        if (!in_array($claims->iss, $expectedIssuers)) {
            throw new \Exception("Issuer mismatch");
        }

        // Validate Expiration (Is the token still valid?)
        if ($claims->exp < $now) {
            throw new \Exception("Token has expired");
        }
    }
}
