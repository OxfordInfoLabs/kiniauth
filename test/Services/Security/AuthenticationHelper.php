<?php


namespace Kiniauth\Test\Services\Security;


use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Application\Session;
use Kinikit\Core\DependencyInjection\Container;

class AuthenticationHelper {

    /**
     * Encrypt a plain text password ready for login.
     *
     * @param $password
     * @return string|null
     */
    public static function encryptPasswordForLogin($password) {
        $session = Container::instance()->get(Session::class);
        return crypt(hash('sha512', $password), User::PASSWORD_SALT_PREFIX . $session->__getSessionSalt());
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
