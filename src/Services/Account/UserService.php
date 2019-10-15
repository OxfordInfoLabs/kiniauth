<?php


namespace Kiniauth\Services\Account;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\TwoFactor\TwoFactorProvider;
use Kinikit\Core\Validation\ValidationException;


class UserService {

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @var TwoFactorProvider
     */
    private $twoFactorProvider;

    /**
     * UserService constructor.
     *
     * @param AuthenticationService $authenticationService
     * @param TwoFactorProvider $twoFactorProvider
     */
    public function __construct($authenticationService, $twoFactorProvider) {
        $this->authenticationService = $authenticationService;
        $this->twoFactorProvider = $twoFactorProvider;
    }

    /**
     * Create a brand new user - optionally supply a name, account name and parent account id if relevant.  If no
     * parent Account Id is supplied, the session context will be used.
     *
     * @objectInterceptorDisabled
     */
    public function createWithAccount($emailAddress, $password, $name = null, $accountName = null, $parentAccountId = 0) {

        // Create a new user, save it and return it back.
        $user = new User($emailAddress, $password, $name, $parentAccountId);
        if ($validationErrors = $user->validate()) {
            throw new ValidationException($validationErrors);
        }

        // Create an account to match with any name we can find.
        $account = new Account($accountName ? $accountName : ($name ? $name : $emailAddress), $parentAccountId);
        $account->save();

        $user->setRoles(array(new UserRole(Role::SCOPE_ACCOUNT, $account->getAccountId())));
        $user->save();

        $user = User::fetch($user->getId());

        return $user;

    }


    /**
     * Create an admin user.
     *
     * @param $emailAddress
     * @param $password
     * @param null $name
     *
     */
    public function createAdminUser($emailAddress, $password, $name = null) {

        // Create a new user, save it and return it back.
        $user = new User($emailAddress, $password, $name);
        if ($validationErrors = $user->validate()) {
            throw new ValidationException($validationErrors);
        }

        $user->setRoles(array(new UserRole(Role::SCOPE_ACCOUNT, 0)));
        $user->save();

        return $user;
    }

    /**
     * @param $newEmailAddress
     * @param $password
     * @param string $userId
     */
    public function changeUserEmail($newEmailAddress, $password, $userId = User::LOGGED_IN_USER) {
        /** @var User $user */
        $user = User::fetch($userId);
        if ($this->authenticationService->validateUserPassword($user->getEmailAddress(), $password)) {
            $user->setEmailAddress($newEmailAddress);
            $user->save();
            return $user;
        }
    }

    /**
     * @param $newMobile
     * @param $password
     * @param string $userId
     * @return User
     */
    public function changeUserMobile($newMobile, $password, $userId = User::LOGGED_IN_USER) {
        /** @var User $user */
        $user = User::fetch($userId);
        if ($this->authenticationService->validateUserPassword($user->getEmailAddress(), $password)) {
            $user->setMobileNumber($newMobile);
            $user->save();
            return $user;
        }
    }

    /**
     * @param $newEmailAddress
     * @param $password
     * @param string $userId
     * @return User
     */
    public function changeUserBackupEmail($newEmailAddress, $password, $userId = User::LOGGED_IN_USER) {
        /** @var User $user */
        $user = User::fetch($userId);
        if ($this->authenticationService->validateUserPassword($user->getEmailAddress(), $password)) {
            $user->setBackupEmailAddress($newEmailAddress);
            $user->save();
            return $user;
        }
    }


    public function generateTwoFactorSettings($userId = User::LOGGED_IN_USER) {

        /** @var User $user */
        $user = User::fetch($userId);

        $this->twoFactorProvider->setAccountName($user->getEmailAddress());

        $secret = $this->twoFactorProvider->createSecretKey();
        $qrCode = $this->twoFactorProvider->generateQRCode($secret);

        return array("secret" => $secret, "qrCode" => $qrCode);
    }

    public function authenticateNewTwoFactor($code, $secret, $userId = User::LOGGED_IN_USER) {

        /** @var User $user */
        $user = User::fetch($userId);

        $authenticated = $this->twoFactorProvider->authenticate($secret, $code);

        if ($authenticated) {
            $user->setTwoFactorData($secret);
            $user->save();
            return $user;
        }
        return false;
    }

    public function disableTwoFactor($userId = User::LOGGED_IN_USER) {

        /** @var User $user */
        $user = User::fetch($userId);

        $user->setTwoFactorData(null);
        $user->save();
        return $user;
    }


}
