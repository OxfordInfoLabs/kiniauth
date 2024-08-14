<?php

namespace Kiniauth\Services\Security\APIAuthentication;

use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Services\Security\Captcha\GoogleRecaptchaProvider;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\MVC\Request\Request;

class CaptchaApiAuthenticator implements APIAuthenticator {

    public function __construct(
        private SecurityService $securityService,
    ) {
    }

    public function authenticate(APIKey $apiKey, Request $request): void {

        $recaptchaSecret = $apiKey->getConfig()["recaptchaSecret"];
        $recaptchaProvider = new GoogleRecaptchaProvider($recaptchaSecret);

        $headers = $request->getHeaders();
        $verificationData = $headers->getCustomHeader("X_CAPTCHA_TOKEN");

        if ($recaptchaProvider->verifyCaptcha($verificationData, $request)) {
            $this->securityService->login($apiKey);
        } else {
            throw new \Exception("Bad Captcha");
        }


    }
}