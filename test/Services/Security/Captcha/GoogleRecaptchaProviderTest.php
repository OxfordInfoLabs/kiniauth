<?php

namespace Kiniauth\Test\Services\Security\Captcha;

use Kiniauth\Services\Security\Captcha\GoogleRecaptchaProvider;
use Kiniauth\Test\TestBase;

include_once __DIR__ . "/../../../autoloader.php";

class GoogleRecaptchaProviderTest extends TestBase {


    public function testDefaultConfiguredTestSecretKeyAlwaysSucceeds() {


        $provider = new GoogleRecaptchaProvider();

        $this->assertTrue($provider->verifyCaptcha("EERTEWERTT"));
        $this->assertTrue($provider->verifyCaptcha("453543543"));

    }


    public function testBadlyConfiguredKeyReturnsFalse(){

        $provider = new GoogleRecaptchaProvider("TREERTR");
        $this->assertFalse($provider->verifyCaptcha("TUIUIUI"));
        $this->assertFalse($provider->verifyCaptcha("1234455"));

    }

}
