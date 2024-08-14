<?php

namespace Kiniauth\Test\Services\Security\APIAuthentication;

use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Services\Security\APIAuthentication\CaptchaApiAuthenticator;
use Kiniauth\Services\Security\Captcha\GoogleRecaptchaProvider;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\MVC\Request\Headers;
use Kinikit\MVC\Request\Request;

include_once "autoloader.php";

class CaptchaApiAuthenticatorTest extends TestBase {

    private SecurityService $securityService;

    public function setUp(): void {
        $this->securityService = MockObjectProvider::instance()->getMockInstance(SecurityService::class);
    }

    public function tearDown(): void {
        GoogleRecaptchaProvider::$testMode = null;
    }

    public function testDoesAuthenticateApiKeysCorrectly() {

        $authenticator = new CaptchaApiAuthenticator($this->securityService);

        GoogleRecaptchaProvider::$testMode = true;

        $apiKey = new APIKey(
            description: null, apiKey: "testKey", apiSecret: "testSecret", config: ["recaptchaSecret" => "myCaptchaSecret"]
        );

        $request = MockObjectProvider::instance()->getMockInstance(Request::class);
        $mockHeaders = MockObjectProvider::instance()->getMockInstance(Headers::class);

        $request->returnValue("getHeaders", $mockHeaders);
        $mockHeaders->returnValue("getCustomHeader", "myCaptchaKey", "X_CAPTCHA_TOKEN");

        $authenticator->authenticate($apiKey, $request);
        $this->assertTrue($this->securityService->methodWasCalled("login", [$apiKey]));

    }

    public function testDoesFailWhenCaptchaIsBad() {

        $authenticator = new CaptchaApiAuthenticator($this->securityService);

        GoogleRecaptchaProvider::$testMode = false;

        $apiKey = new APIKey(
            description: null, apiKey: "testKey", apiSecret: "testSecret", config: ["recaptchaSecret" => "myCaptchaSecret"]
        );

        $request = MockObjectProvider::instance()->getMockInstance(Request::class);
        $mockHeaders = MockObjectProvider::instance()->getMockInstance(Headers::class);

        $request->returnValue("getHeaders", $mockHeaders);
        $mockHeaders->returnValue("getCustomHeader", "myCaptchaKey", "X_CAPTCHA_TOKEN");

        try {
            $authenticator->authenticate($apiKey, $request);
            $this->fail("Should have thrown here.");
        } catch (\Exception $e) {
            $this->assertEquals("Bad Captcha", $e->getMessage());
        }

    }

}