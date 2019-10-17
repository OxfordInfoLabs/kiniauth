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


}
