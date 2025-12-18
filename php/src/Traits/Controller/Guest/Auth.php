<?php


namespace Kiniauth\Traits\Controller\Guest;


use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Application\SessionData;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\ValueObjects\Security\NewPasswordDescriptor;
use Kinikit\Core\DependencyInjection\Container;


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
        return $this->authenticationService->login($payload["emailAddress"], $payload["password"], $payload["clientTwoFactorData"] ?? null);
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
     * @http POST /twoFactor
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
     * @http POST /passwordResetRequest
     *
     * @param $emailAddress
     *
     * @captcha
     *
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


    /**
     * @http POST /sessionTransfer
     *
     * @param string $sessionToken
     */
    public function sessionTransfer($sessionToken) {
        $this->authenticationService->activateSessionUsingTransferToken($sessionToken);
        return Container::instance()->get(SessionData::class);
    }

    /**
     * @http GET /sso/$authenticatorKey/$providerKey
     *
     * @param string $providerKey
     * @return string
     */
    public function initialiseSSO($authenticatorKey, $providerKey) {
        return $this->authenticationService->initialiseSSO($authenticatorKey, $providerKey);
    }

    /**
     * @http GET /oidc/$providerKey
     *
     * @param string $providerKey
     * @param string $code
     * @param string $state
     * @return void
     */
    public function authenticateOpenId($providerKey, $code, $state) {
        $data = [$code, $state];
        $this->authenticationService->authenticateBySSO($providerKey, $data, "openid");
    }

    /**
     * Required for IdP to configure their end
     *
     * @http GET /saml/metadata/$providerKey
     *
     * @param string $providerKey
     * @return void
     */
    public function getSAMLMetadata($providerKey) {
        $this->authenticationService->getSAMLMetadata($providerKey);
    }

    /**
     * @http POST /saml
     *
     * @param string $providerKey
     * @param mixed $data
     * @return void
     */
    public function authenticateSAML($providerKey, $data) {
        $this->authenticationService->authenticateBySSO($providerKey, $data, "saml");
    }

    /**
     * @http POST /sso/$providerKey
     *
     * @param string $providerKey
     * @param mixed $data
     */
    public function authenticateSSO($providerKey, $data) {
        $this->authenticationService->authenticateBySSO($providerKey, $data);
    }


    /**
     * @http POST /joinWithToken
     *
     * @param string $joinToken
     * @return void
     * @throws \Kinikit\Core\Exception\AccessDeniedException
     */
    public function authenticateByJoinToken($joinToken) {
        $this->authenticationService->joinAccountUsingToken($joinToken);
    }

}
