<?php

namespace Kiniauth\Test\Services\Security\JWT;

use Kiniauth\Services\Security\JWT\JWTAlg;
use Kiniauth\Services\Security\JWT\JWTManager;
use PHPUnit\Framework\TestCase;

require_once 'autoloader.php';

class JWTManagerTest extends TestCase {

    private const SECRET_KEY = 'super_secret_key_for_testing';

    /**
     * Test successful token creation with default HS256 algorithm.
     */
    public function testCreateTokenWithDefaultAlgorithm(): void {
        $manager = new JWTManager(JWTAlg::HS256, self::SECRET_KEY);
        $payload = ['user_id' => 123, 'role' => 'admin', 'exp' => time() + 3600];

        $token = $manager->createToken($payload);

        $this->assertIsString($token);
        $this->assertCount(3, explode('.', $token), 'Token should have three parts separated by dots.');

    }

    /**
     * Test successful token creation with a custom algorithm (even though the internal logic is fixed to sha256).
     */
    public function testCreateTokenWithCustomAlgorithm(): void {

        $customAlg = JWTAlg::HS512;
        $manager = new JWTManager($customAlg, self::SECRET_KEY);
        $payload = ['data' => 'test'];

        $token = $manager->createToken($payload);

        [$encodedHeader, ,] = explode('.', $token);

        // Decode the header to check the 'alg' value
        $decodedHeaderJson = base64_decode(strtr($encodedHeader, '-_', '+/'));
        $decodedHeader = json_decode($decodedHeaderJson, true);

        $this->assertArrayHasKey('alg', $decodedHeader);
        $this->assertSame($customAlg->name, $decodedHeader['alg'], 'Header should reflect the custom algorithm.');

    }

    /**
     * Test successful token validation.
     */
    public function testValidateTokenSuccess(): void {

        $manager = new JWTManager(JWTAlg::HS256, self::SECRET_KEY);
        $payload = ['data' => 'test validation'];
        $token = $manager->createToken($payload);

        $isValid = $manager->validateToken($token);

        $this->assertTrue($isValid, 'Valid token should pass validation.');

    }

    /**
     * Test token validation failure due to a bad secret key.
     */
    public function testValidateTokenFailureWithWrongKey(): void {

        $manager = new JWTManager(JWTAlg::HS256, self::SECRET_KEY);
        $payload = ['data' => 'test validation failure'];
        $token = $manager->createToken($payload);

        // Create a new manager with a *different* key
        $wrongManager = new JWTManager(JWTAlg::HS256, 'a_different_secret_key');

        $isValid = $wrongManager->validateToken($token);

        $this->assertFalse($isValid, 'Token created with a different key should fail validation.');

    }

    /**
     * Test token validation failure due to tampered payload.
     */
    public function testValidateTokenFailureWithTamperedPayload(): void {

        $manager = new JWTManager(JWTAlg::HS256, self::SECRET_KEY);
        $payload = ['data' => 'original payload'];
        $originalToken = $manager->createToken($payload);

        [$encodedHeader, $encodedPayload, $encodedSignature] = explode('.', $originalToken);

        // Tamper the payload: change the first character of the encoded payload
        $tamperedPayload = 'A' . substr($encodedPayload, 1);
        $tamperedToken = $encodedHeader . '.' . $tamperedPayload . '.' . $encodedSignature;

        $isValid = $manager->validateToken($tamperedToken);

        $this->assertFalse($isValid, 'Tampered token (payload changed) should fail validation.');

    }

    /**
     * Test successful token decoding.
     */
    public function testDecodeTokenSuccess(): void {

        $manager = new JWTManager(JWTAlg::HS256, self::SECRET_KEY);
        $originalPayload = [
            'iat' => time(),
            'iss' => 'test-issuer',
            'sub' => 'test-subject'
        ];

        $token = $manager->createToken($originalPayload);
        $decodedPayload = $manager->decodeToken($token);

        $this->assertIsArray($decodedPayload);

        // The original payload keys should be present in the decoded payload
        $this->assertArrayHasKey('iat', $decodedPayload);
        $this->assertArrayHasKey('iss', $decodedPayload);
        $this->assertArrayHasKey('sub', $decodedPayload);

        // Check that the values match
        $this->assertSame($originalPayload['iat'], $decodedPayload['iat']);
        $this->assertSame($originalPayload['iss'], $decodedPayload['iss']);
        $this->assertSame($originalPayload['sub'], $decodedPayload['sub']);

    }

    /**
     * Test decoding a token with an empty payload.
     */
    public function testDecodeTokenWithEmptyPayload(): void {

        $manager = new JWTManager(JWTAlg::HS256, self::SECRET_KEY);
        $originalPayload = [];

        $token = $manager->createToken($originalPayload);
        $decodedPayload = $manager->decodeToken($token);

        $this->assertIsArray($decodedPayload);
        $this->assertEmpty($decodedPayload, 'Decoding an empty payload should result in an empty array.');

    }

    /**
     * Test end-to-end token flow: Create, Validate, Decode.
     */
    public function testFullTokenLifecycle(): void {

        $manager = new JWTManager(JWTAlg::HS256, self::SECRET_KEY);
        $originalPayload = ['username' => 'testuser', 'time' => microtime(true)];

        $token = $manager->createToken($originalPayload);

        // 1. Validate
        $this->assertTrue($manager->validateToken($token), 'Token must be valid immediately after creation.');

        // 2. Decode
        $decodedPayload = $manager->decodeToken($token);

        // 3. Check Decoded Data
        $this->assertSame($originalPayload['username'], $decodedPayload['username']);
        $this->assertSame($originalPayload['time'], $decodedPayload['time']);

    }

}