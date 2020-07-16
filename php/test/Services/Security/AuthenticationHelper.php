<?php


namespace Kiniauth\Test\Services\Security;


use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\AuthenticationService;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Security\Hash\SHA512HashProvider;

class AuthenticationHelper {


    /**
     * Login as a user - handles mapping to correct password format
     *
     * @param $username
     * @param $password
     */
    public static function login($emailAddress, $password) {
        $authenticationService = Container::instance()->get(AuthenticationService::class);
        return $authenticationService->login($emailAddress, self::encryptPasswordForLogin($password . $emailAddress));
    }


    /**
     * Logout function
     *
     * @return mixed
     */
    public static function logout() {
        $authenticationService = Container::instance()->get(AuthenticationService::class);
        return $authenticationService->logout();
    }


    /**
     * Encrypt a plain text password ready for login.
     *
     * @param $password
     * @return string|null
     */
    public static function encryptPasswordForLogin($password) {
        $session = Container::instance()->get(Session::class);

        /**
         * @var SHA512HashProvider $hashProvider
         */
        $hashProvider = Container::instance()->get(SHA512HashProvider::class);

        return $hashProvider->generateHash($hashProvider->generateHash($password) . $session->__getSessionSalt());
    }


    /**
     * Hash a new password for a new user or change of password
     *
     * @param $password
     * @return string
     */
    public static function hashNewPassword($password) {
        return hash('sha512', $password);
    }

}
