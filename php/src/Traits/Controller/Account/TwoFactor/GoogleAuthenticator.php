<?php


namespace Kiniauth\Traits\Controller\Account\TwoFactor;


use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Security\TwoFactor\GoogleAuthenticatorProvider;

trait GoogleAuthenticator {


    /**
     * @var GoogleAuthenticatorProvider
     */
    private $googleAuthenticatorProvider;


    /**
     * GoogleAuthenticator constructor.
     *
     * @param GoogleAuthenticatorProvider $googleAuthenticatorProvider
     */
    public function __construct($googleAuthenticatorProvider) {
        $this->googleAuthenticatorProvider = $googleAuthenticatorProvider;
    }


    /**
     * Generate two factor settings
     *
     * @http GET /twoFactorSettings
     *
     * @return array
     */
    public function createTwoFactorSettings($userId = User::LOGGED_IN_USER) {
        return $this->googleAuthenticatorProvider->generateTwoFactorSettings($userId);
    }

    /**
     * @http GET /newTwoFactor
     *
     * @param $code
     * @param $secret
     * @return bool|\Kiniauth\Objects\Security\User
     */
    public function authenticateNewTwoFactorCode($code, $secret, $userId = User::LOGGED_IN_USER) {
        return $this->googleAuthenticatorProvider->authenticateNewTwoFactor($code, $secret, $userId);
    }

    /**
     * Disable the current logged in users two fa.
     *
     * @http GET /disableTwoFA
     *
     * @return \Kiniauth\Objects\Security\User
     */
    public function disableTwoFactor($userId = User::LOGGED_IN_USER) {
        return $this->googleAuthenticatorProvider->disableTwoFactor($userId);
    }


}