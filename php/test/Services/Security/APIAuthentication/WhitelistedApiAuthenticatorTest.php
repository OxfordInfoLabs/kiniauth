<?php

namespace Kiniauth\Test\Services\Security\APIAuthentication;

use Kiniauth\Exception\Security\InvalidIPAddressAPIKeyException;
use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Objects\Security\WhitelistedIPRangeProfile;
use Kiniauth\Services\Security\APIAuthentication\WhitelistedApiAuthenticator;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\MVC\Request\Request;

include_once "autoloader.php";

class WhitelistedApiAuthenticatorTest extends TestBase {

    private SecurityService $securityService;

    public function setUp(): void {
        $this->securityService = MockObjectProvider::instance()->getMockInstance(SecurityService::class);
    }

    public function testDoesAuthenticateApiKeysCorrectly() {

        $authenticator = new WhitelistedApiAuthenticator($this->securityService);

        $profile = new WhitelistedIPRangeProfile("192.168.0.0/24");

        $apiKey = MockObjectProvider::instance()->getMockInstance(APIKey::class);
        $apiKey->returnValue("returnWhitelistedProfile", $profile);

        $request = MockObjectProvider::instance()->getMockInstance(Request::class);
        $request->returnValue("getRemoteIPAddress", "192.168.0.1");

        $authenticator->authenticate($apiKey, $request);
        $this->assertTrue($this->securityService->methodWasCalled("login", [$apiKey]));

    }

    public function testDoesFailWhenApiKeyHasNoWhitelistedIpAddressesProfile() {

        $authenticator = new WhitelistedApiAuthenticator($this->securityService);

        $apiKey = MockObjectProvider::instance()->getMockInstance(APIKey::class);
        $apiKey->returnValue("returnWhitelistedProfile", null);

        $request = MockObjectProvider::instance()->getMockInstance(Request::class);
        $request->returnValue("getRemoteIPAddress", "192.168.0.1");

        try {
            $authenticator->authenticate($apiKey, $request);
            $this->fail("Should have thrown here");
        } catch (InvalidIPAddressAPIKeyException) {
            $this->assertFalse($this->securityService->methodWasCalled("login", [$apiKey]));
        }

    }

}