<?php

namespace Kiniauth\Services\Security\APIAuthentication;

use Kiniauth\Exception\Security\InvalidIPAddressAPIKeyException;
use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\MVC\Request\Request;

class WhitelistedApiAuthenticator implements APIAuthenticator {

    public function __construct(
        private SecurityService $securityService
    ) {
    }

    /**
     * @param APIKey $apiKey
     * @param Request $request
     * @return void
     */
    public function authenticate(APIKey $apiKey, Request $request): void {

        $profile = $apiKey->returnWhitelistedProfile();
        $ipAddress = $request->getRemoteIPAddress();

        if ($profile && $profile->isAddressWhitelisted($ipAddress)) {
            $this->securityService->login($apiKey);
        } else {
            throw new InvalidIPAddressAPIKeyException();
        }

    }

}