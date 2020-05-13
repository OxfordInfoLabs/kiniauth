<?php


namespace Kiniauth\Traits\Controller\Guest;


use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\ValueObjects\Security\NewPasswordDescriptor;



trait Auth {

    protected $authenticationService;

    protected $userService;


    /**
     * @param AuthenticationService $authenticationService
     * @param UserService $userService
     */
    public function __construct($authenticationService, $userService) {
        $this->authenticationService = $authenticationService;
        $this->userService = $userService;
    }


    /**
     * Log in with an email address and password.
     *
     * @http GET /login
     *
     * @param $emailAddress
     * @param $password
     *
     * @captcha 1
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


    /**
     * Request a password reset
     *
     * @http GET /passwordReset
     *
     * @param $emailAddress
     */
    public function requestPasswordReset($emailAddress) {
        $this->userService->sendPasswordReset($emailAddress);
    }


    /**
     * Reset the password using a new password descriptor
     *
     * @http POST /passwordReset
     *
     * @param NewPasswordDescriptor $newPasswordDescriptor
     */
    public function resetPassword($newPasswordDescriptor) {
        $this->userService->changePassword($newPasswordDescriptor->getResetCode(), $newPasswordDescriptor->getNewPassword());
    }


}
