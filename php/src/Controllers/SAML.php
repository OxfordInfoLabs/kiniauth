<?php

namespace Kiniauth\Controllers;

use Kiniauth\Services\Security\AuthenticationService;
use Kinikit\MVC\Response\SimpleResponse;

class SAML {

    private $authenticationService;

    /**
     * @param AuthenticationService $authenticationService
     */
    public function __construct($authenticationService) {
        $this->authenticationService = $authenticationService;
    }


    /**
     * Get the application metadata
     *
     * @http GET /metadata/$providerKey
     *
     * @param string $providerKey
     * @return SimpleResponse
     */
    public function getSAMLMetadata($providerKey): SimpleResponse {
        return new SimpleResponse($this->authenticationService->getSAMLMetadata($providerKey));
    }

}