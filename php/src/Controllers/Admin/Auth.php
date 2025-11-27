<?php

namespace Kiniauth\Controllers\Admin;

use Kiniauth\Services\Security\AuthenticationService;

class Auth {

    public function __construct(private AuthenticationService $authenticationService) {
    }


    /**
     * @http GET /joinAccountToken/$accountId
     *
     * @param $accountId
     * @return string
     */
    public function createJoinAccountToken($accountId){
        return $this->authenticationService->createJoinAccountToken($accountId);
    }

}