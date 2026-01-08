<?php

namespace Kiniauth\Test\Services\Security\SSO;

use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\EncryptionService;
use Kiniauth\Services\Security\JWT\JWTManager;
use Kiniauth\Services\Security\SSOProvider\OpenIdAuthenticator;
use Kiniauth\ValueObjects\Security\SSO\OpenIdAuthenticatorConfiguration;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\HTTP\Response\Response;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class OpenIdAuthenticatorTest extends TestCase {

    private $requestDispatcherMock;
    private $sessionMock;
    private $configMock;
    private $jwtManagerMock;
    private $encryptionServiceMock;
    private $authenticator;

    protected function setUp(): void {
        // Create Mock objects for all dependencies
        $this->requestDispatcherMock = MockObjectProvider::mock(HttpRequestDispatcher::class);
        $this->sessionMock = MockObjectProvider::mock(Session::class);
        $this->configMock = MockObjectProvider::mock(OpenIdAuthenticatorConfiguration::class);
        $this->jwtManagerMock = MockObjectProvider::mock(JWTManager::class);
        $this->encryptionServiceMock = MockObjectProvider::mock(EncryptionService::class);

        // Instantiate the class under test with the mocks
        $this->authenticator = new OpenIdAuthenticator(
            $this->requestDispatcherMock,
            $this->sessionMock,
            $this->configMock,
            $this->jwtManagerMock,
            $this->encryptionServiceMock
        );

        // Set up common configuration values
        $this->configMock->returnValue('getRedirectUri', 'http://redirect.uri');
        $this->configMock->returnValue('getTokenEndpoint', 'http://token.endpoint');
        $this->configMock->returnValue('getIssuer', 'https://issuer.com');
        $this->configMock->returnValue('getClientId', 'test-client-id');
        $this->configMock->returnValue('getClientSecret', 'some_client_secret');

        $this->encryptionServiceMock->returnValue("decrypt", "test_client_secret", [Configuration::readParameter("sso.oidc.masterKey"), "some_client_secret"]);
    }

    // --- Test Initialisation ---
    public function testInitialiseReturnsCorrectUrlAndSetsSession(): void {

        $this->configMock->returnValue('getAuthorisationEndpoint', 'https://auth.example.com/login');
        $this->configMock->returnValue('getClientId', 'test_client_id');
        $this->configMock->returnValue('getRedirectUri', 'https://app.example.com/callback');

        $url = $this->authenticator->initialise();

        $this->assertStringStartsWith('https://auth.example.com/login?', $url);
        $this->assertStringContainsString('client_id=test_client_id', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('state=', $url);
        $this->assertStringContainsString('nonce=', $url);
    }

    // --- Test Case 1: Successful Authentication ---
    public function testAuthenticate_Success() {
        $code = 'valid-auth-code';
        $state = 'valid-state';
        $expectedEmail = 'user@example.com';
        $idToken = 'valid-id-token';
        $accessToken = 'valid-access-token';
        $tokenResponseBody = json_encode(['access_token' => $accessToken, 'id_token' => $idToken]);

        // 1. Mock Session for State validation
        $this->sessionMock->returnValue("getValue", $state, ["oidc_state"]);
        $this->sessionMock->returnValue("getValue", "expected-nonce", ["oidc_nonce"]);

        // 2. Mock Token Exchange (requestTokens)
        $tokenResponseMock = MockObjectProvider::mock(Response::class);
        $tokenResponseMock->returnValue('getStatusCode', 200);
        $tokenResponseMock->returnValue('getBody', $tokenResponseBody);

        $expectedTokenRequest = new Request(
            'http://token.endpoint',
            Request::METHOD_POST,
            [
                "grant_type" => "authorization_code",
                "code" => $code,
                "redirect_uri" => 'http://redirect.uri',
                "client_id" => "test-client-id",
                "client_secret" => "test_client_secret"
            ],
            null,
            new Headers(["Content-Type" => "application/x-www-form-urlencoded"])
        );
        $this->requestDispatcherMock->returnValue("dispatch", $tokenResponseMock, $expectedTokenRequest);

        // 3. Mock ID Token Validation (validateIdToken)
        $this->mockIdTokenValidation($idToken, $expectedEmail); // Helper function to set up validation expectations

        // Execute and Assert
        $result = $this->authenticator->authenticate([$code, $state]);

        $this->assertEquals($expectedEmail, $result, "The authenticated email should match the expected email.");
    }

    // --- Test Case 2: State Mismatch Failure ---
    public function testAuthenticate_ThrowsAccessDeniedExceptionOnStateMismatch() {

        $this->sessionMock->returnValue('getValue', 'expected-state', 'oidc_state');


        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage("Invalid state");
        $this->authenticator->authenticate(['some-code', 'mismatched-state']);

    }

    // --- Test Case 3: Token Exchange Failure (HTTP Status) ---
    public function testAuthenticate_ThrowsExceptionOnTokenRequestFailure() {
        $state = 'valid-state';

        // 1. State check passes
        $this->sessionMock->returnValue("getValue", $state, "oidc_state");

        // 2. Token request fails (e.g., 400 status)
        $failedResponseMock = $this->getMockedResponse(400, '{"error": "invalid_grant"}');

        $this->requestDispatcherMock->returnValue("dispatch", $failedResponseMock);


        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Token request failed");
        $this->authenticator->authenticate(['auth-code', $state]);

    }

    // --- Test Case 4: ID Token Validation Failure (Issuer Mismatch) ---
    public function testAuthenticate_ThrowsAccessDeniedExceptionOnIssuerMismatch() {
        $state = 'valid-state';
        $idToken = 'token-with-bad-issuer';
        $accessToken = 'valid-access-token';
        $tokenResponseBody = json_encode(['access_token' => $accessToken, 'id_token' => $idToken]);

        // 1. State check passes
        $this->sessionMock->returnValue("getValue", $state, "oidc_state");

        // 2. Token request succeeds
        $tokenResponseMock = $this->getMockedResponse(200, $tokenResponseBody);
        $this->requestDispatcherMock->returnValue('dispatch', $tokenResponseMock);

        // 3. ID Token validation setup
        $this->jwtManagerMock->returnValue('validateToken', true, $idToken);

        $this->jwtManagerMock->returnValue('decodeToken', [
            "iss" => "https://wrong-issuer.com", // Mismatch
            "aud" => "test-client-id",
            "exp" => time() + 3600, // Not expired
            "nonce" => "expected-nonce",
        ], $idToken);

        $this->sessionMock->returnValue('getValue', 'expected-nonce', 'oidc_nonce');

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage("Invalid issuer");
        $this->authenticator->authenticate(['auth-code', $state]);
    }

    // --- Test Case 5: ID Token Expiration Failure ---
    public function testAuthenticate_ThrowsAccessDeniedExceptionOnTokenExpiration() {
        $state = 'valid-state';
        $idToken = 'expired-token';
        $accessToken = 'valid-access-token';
        $tokenResponseBody = json_encode(['access_token' => $accessToken, 'id_token' => $idToken]);

        // 1. State check passes
        $this->sessionMock->returnValue('getValue', $state, 'oidc_state');

        // 2. Token request succeeds
        $tokenResponseMock = $this->getMockedResponse(200, $tokenResponseBody);
        $this->requestDispatcherMock->returnValue('dispatch', $tokenResponseMock);

        // 3. ID Token validation setup
        $this->jwtManagerMock->returnValue('validateToken', true, $idToken);

        $this->jwtManagerMock->returnValue('decodeToken', [
            "iss" => "https://issuer.com",
            "aud" => "test-client-id",
            "exp" => time() - 3600, // Expired time (way past the 30s leeway)
            "nonce" => "expected-nonce",
        ], $idToken);

        $this->sessionMock->returnValue('getValue', 'expected-nonce', 'oidc_nonce');

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage("Token expired");

        $this->authenticator->authenticate(['auth-code', $state]);

    }

    // --- Helper Methods ---

    /**
     * Helper to create a mocked Response object.
     */
    private function getMockedResponse(int $statusCode, string $body): Response {
        $response = MockObjectProvider::mock(Response::class);
        $response->returnValue('getStatusCode', $statusCode);
        $response->returnValue('getBody', $body);
        return $response;
    }

    /**
     * Helper to mock successful ID Token validation expectations.
     */
    private function mockIdTokenValidation(string $idToken, string $email): void {
        $this->jwtManagerMock->returnValue('validateToken', true, $idToken);

        $this->jwtManagerMock->returnValue('decodeToken', [
            "iss" => "https://issuer.com",
            "aud" => "test-client-id",
            "exp" => time() + 3600, // Not expired
            "nonce" => "expected-nonce",
            "email" => $email
        ], $idToken);
    }
}