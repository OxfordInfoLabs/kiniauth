<?php

namespace Kiniauth\Services\Security\APIAuthentication;

use Kiniauth\Objects\Security\APIKey;
use Kinikit\MVC\Request\Request;

/**
 * @implementation captcha \Kiniauth\Services\Security\APIAuthentication\CaptchaApiAuthenticator
 * @implementation whitelisted \Kiniauth\Services\Security\APIAuthentication\WhitelistedApiAuthenticator
 */
interface APIAuthenticator {

    /**
     * @param APIKey $apiKey
     * @param Request $request
     * @return void
     */
    public function authenticate(APIKey $apiKey, Request $request): void;

}