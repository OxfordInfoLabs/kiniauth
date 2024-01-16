<?php

namespace Kiniauth\Test\Services\Security\Captcha;

use Kiniauth\Services\Security\Captcha\GoogleRecaptchaProvider;
use Kiniauth\Test\TestBase;

include_once __DIR__ . "/../../../autoloader.php";

class GoogleRecaptchaProviderTest extends TestBase {


    public function testDefaultConfiguredTestSecretKeyAlwaysSucceedsForV2() {


        $provider = new GoogleRecaptchaProvider();

        $this->assertTrue($provider->verifyCaptcha("EERTEWERTT"));
        $this->assertTrue($provider->verifyCaptcha("453543543"));

    }


    public function testBadlyConfiguredKeyReturnsFalseForV2() {

        $provider = new GoogleRecaptchaProvider("TREERTR");
        $this->assertFalse($provider->verifyCaptcha("TUIUIUI"));
        $this->assertFalse($provider->verifyCaptcha("1234455"));

    }


    public function testDefaultConfiguredTestSecretWithScoreSucceedsIfThresholdExceeded() {

        $provider = new GoogleRecaptchaProvider(null, 0);
        $this->assertTrue($provider->verifyCaptcha("EERTEWERTT"));

        $provider = new GoogleRecaptchaProvider(null, 0.5);
        $this->assertFalse($provider->verifyCaptcha("EERTEWERTT"));

    }


}
