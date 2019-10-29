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
     * @http GET /validatePassword
     *
     * @param $emailAddress
     * @param $password
     * @param null $parentAccountId
     * @return bool
     */
    public function validateUserPassword($emailAddress, $password, $parentAccountId = null) {
        return $this->authenticationService->validateUserPassword($emailAddress, $password, $parentAccountId);
    }


}
