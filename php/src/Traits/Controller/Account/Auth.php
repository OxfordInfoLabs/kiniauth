<?php


namespace Kiniauth\Traits\Controller\Account;


trait Auth {


    private $authenticationService;

    /**
     * @param \Kiniauth\Services\Security\AuthenticationService $authenticationService
     */
    public function __construct($authenticationService) {
        $this->authenticationService = $authenticationService;
    }


    /**
     * Check if email exists
     *
     * @http GET /emailExists
     *
     * @param $emailAddress
     * @return bool
     */
    public function emailExists($emailAddress) {
        return $this->authenticationService->emailExists($emailAddress);
    }

    /**
     * Validate the users password
     *
     * @http POST /validatePassword
     *
     * @param mixed $payload
     * @return bool
     */
    public function validateUserPassword($payload) {
        return $this->authenticationService->validateUserPassword($payload["emailAddress"], $payload["password"], $payload["parentAccountId"] ?? null);
    }


    /**
     * Generate a session transfer token
     *
     * @http GET /sessionTransfer
     *
     * @return string
     *
     */
    public function generateSessionTransferToken() {
        return $this->authenticationService->generateSessionTransferToken();
    }


}
