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
     * @http POST /login
     *
     * @param $payload
     *
     * @captcha 1
     */
    public function logIn($payload) {
        return $this->authenticationService->login($payload["emailAddress"], $payload["password"]);
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
     * Close active sessions (when working with single session logins)
     *
     * @http GET /closeActiveSessions
     */
    public function closeActiveSessions() {
        return $this->authenticationService->closeActiveSessionsAndLogin();
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
     *
     * @captcha
     */
    public function requestPasswordReset($emailAddress) {
        $this->userService->sendPasswordReset($emailAddress);
    }


    /**
     * Get a reset code for an email address
     *
     * @http GET /passwordReset/$resetCode
     *
     * @param $resetCode
     * @return string
     */
    public function getEmailAddressForResetCode($resetCode) {
        return $this->userService->getEmailForPasswordResetCode($resetCode);
    }


    /**
     * Reset the password using a new password descriptor
     *
     * @http POST /passwordReset
     *
     * @param NewPasswordDescriptor $newPasswordDescriptor
     *
     * @captcha
     */
    public function resetPassword($newPasswordDescriptor) {
        $this->userService->changePassword($newPasswordDescriptor->getResetCode(), $newPasswordDescriptor->getNewPassword());
    }


    /**
     * @http GET /unlockUser/$unlockCode
     *
     * @param $unlockCode
     */
    public function unlockUser($unlockCode) {
        $this->userService->unlockUser($unlockCode);
    }


}
