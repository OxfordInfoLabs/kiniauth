<?php


namespace Kiniauth\Services\Account;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kinikit\Core\Validation\ValidationException;


class UserService {

    private $authenticationService;

    /**
     * UserService constructor.
     *
     * @param \Kiniauth\Services\Security\AuthenticationService $authenticationService
     */
    public function __construct($authenticationService) {
        $this->authenticationService = $authenticationService;
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

        $user->setRoles(array(new UserRole(0)));
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

}
