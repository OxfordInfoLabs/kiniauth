<?php


namespace Kiniauth\WebServices\ControllerTraits\Guest;


use Kiniauth\Services\Security\AuthenticationService;

trait Auth {

    private $authenticationService;

    /**
     * @param \Kiniauth\Services\Security\AuthenticationService $authenticationService
     */
    public function __construct($authenticationService) {
        $this->authenticationService = $authenticationService;
    }


    /**
     * Log in with an email address and password.
     *
     * @http GET /login
     *
     * @param $emailAddress
     * @param $password
     */
    public function logIn($emailAddress, $password) {
        return $this->authenticationService->login($emailAddress, $password);
    }

    /**
     * Logout
     *
     * @http GET /logout
     */
    public function logOut() {
        $this->authenticationService->logout();
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

    /**
     * Generate two factor settings
     *
     * @http GET /twoFactorSettings
     *
     * @return array
     */
    public function createTwoFactorSettings() {
        return $this->authenticationService->generateTwoFactorSettings();
    }

    /**
     * @http GET /newTwoFactor
     *
     * @param $code
     * @param $secret
     * @return bool|\Kiniauth\Objects\Security\User
     */
    public function authenticateNewTwoFactorCode($code, $secret) {
        return $this->authenticationService->authenticateNewTwoFactor($code, $secret);
    }

    /**
     * Authenticate the two fa code prior to login
     *
     * @http GET /twoFactor
     *
     * @param $code
     * @return bool
     * @throws \Kiniauth\Exception\Security\AccountSuspendedException
     * @throws \Kiniauth\Exception\Security\InvalidLoginException
     * @throws \Kiniauth\Exception\Security\UserSuspendedException
     */
    public function authenticateTwoFactor($code) {
        return $this->authenticationService->authenticateTwoFactor($code);
    }

    /**
     * Disable the current logged in users two fa.
     *
     * @http GET /disableTwoFA
     *
     * @return \Kiniauth\Objects\Security\User
     */
    public function disableTwoFactor() {
        return  $this->authenticationService->disableTwoFactor();
    }
}
