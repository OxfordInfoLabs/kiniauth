<?php

namespace Kiniauth\Test\Services\Security\JWT;

use Kiniauth\Services\Security\JWT\JWTManager;
use Kiniauth\ValueObjects\Security\SSO\OpenIdAuthenticatorConfiguration;
use PHPUnit\Framework\TestCase;

require_once 'autoloader.php';

class JWTManagerTest extends TestCase {

    private const SECRET_KEY = 'super_secret_key_for_testing';

    private function buildJwt(array $claims, string $alg = "HS256"): string {

        $header = base64_encode(json_encode([
            "alg" => $alg,
            "typ" => "JWT"
        ]));

        $payload = base64_encode(json_encode($claims));

        $signature = hash_hmac(
            "sha256",
            $header . "." . $payload,
            self::SECRET_KEY,
            true
        );

        return $header . "." . $payload . "." . base64_encode($signature);
    }

    /**
     * Test that a token of the wrong structure gets thrown
     */
    public function testInvalidTokenStructureSupplied(): void {
        $manager = new JWTManager();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid Token structure");

        $manager->validateToken("hello world");
    }

    /**
     * Test that a valid token returns the correct algorithm
     *
     * @return void
     * @throws \Exception
     */
    public function testAlgorithmReturnedForValidToken() {
        $validToken = $this->buildJwt([
            "iss" => "https://issuer.test",
              "sub" => "user-123",
              "aud" => "test-client-id",
              "exp" => time(),
              "iat" => time(),
              "auth_time" => time(),
              "nonce" => "random-nonce"
        ]);

        $manager = new JWTManager();

        $alg = $manager->validateToken($validToken);
        $this->assertSame("HS256", $alg);
    }

    /**
     * Test that an unsupported algorithm get correctly thrown
     *
     * @return void
     * @throws \Exception
     */
    public function testUnsupportedAlgorithm() {
        $validToken = $this->buildJwt([
            "iss" => "https://issuer.test",
            "sub" => "user-123",
            "aud" => "test-client-id",
            "exp" => time(),
            "iat" => time(),
            "auth_time" => time(),
            "nonce" => "random-nonce"
        ], "NA123");

        $manager = new JWTManager();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Unsupported algorithm: NA123");
        $alg = $manager->validateToken($validToken);
    }


    public function testClaimAudienceClientIdMismatchException() {
        $claims = [
            "iss" => "https://issuer.test",
            "sub" => "user-123",
            "aud" => "test-client-id",
            "exp" => time(),
            "iat" => time(),
            "auth_time" => time(),
            "nonce" => "random-nonce"
        ];

        $clientConfig = new OpenIdAuthenticatorConfiguration(
            "other-client-id",
            "https://issuer.test",
            "https://auth.test",
            "https://token.test",
            "https://redirect.test",
            "test-client-secret",
            "https://jwks.test",
            "https://userinfo.test"
        );

        $manager = new JWTManager();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Audience mismatch: Expected {$clientConfig->getClientId()}");
        $manager->validateClaims((object) $claims, $clientConfig);
    }

    public function testClaimIssuerMismatchException() {
        $claims = [
            "iss" => "https://other-issuer.test",
            "sub" => "user-123",
            "aud" => "test-client-id",
            "exp" => time(),
            "iat" => time(),
            "auth_time" => time(),
            "nonce" => "random-nonce"
        ];

        $clientConfig = new OpenIdAuthenticatorConfiguration(
            "test-client-id",
            "https://issuer.test",
            "https://auth.test",
            "https://token.test",
            "https://redirect.test",
            "test-client-secret",
            "https://jwks.test",
            "https://userinfo.test"
        );

        $manager = new JWTManager();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Issuer mismatch");
        $manager->validateClaims((object) $claims, $clientConfig);
    }

    public function testTokenExpiryInClaimException() {
        $claims = [
            "iss" => "https://issuer.test",
            "sub" => "user-123",
            "aud" => "test-client-id",
            "exp" => time() - 3600,
            "iat" => time(),
            "auth_time" => time(),
            "nonce" => "random-nonce"
        ];

        $clientConfig = new OpenIdAuthenticatorConfiguration(
            "test-client-id",
            "https://issuer.test",
            "https://auth.test",
            "https://token.test",
            "https://redirect.test",
            "test-client-secret",
            "https://jwks.test",
            "https://userinfo.test"
        );

        $manager = new JWTManager();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Token has expired");
        $manager->validateClaims((object) $claims, $clientConfig);
    }

}