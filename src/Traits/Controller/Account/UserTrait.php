<?php

namespace Kiniauth\Traits\Controller\Account;

use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\RoleService;

trait UserTrait {

    private $userService;

    private $session;

    private $roleService;

    /**
     * Account constructor.
     * @param \Kiniauth\Services\Account\UserService $userService
     * @param Session $session
     * @param RoleService $roleService
     */
    public function __construct($userService, $session, $roleService) {
        $this->userService = $userService;
        $this->session = $session;
        $this->roleService = $roleService;
    }

    /**
     * Get a user object by userId (optional), defaults to the logged in user
     *
     * @http GET
     *
     * @param string $userId
     *
     * @return User
     */
    public function getUser($userId = User::LOGGED_IN_USER) {
        return User::fetch($userId);
    }

    /**
     * Change the logged in users email
     *
     * @http GET /changeEmail
     *
     * @param $newEmailAddress
     * @param $password
     * @return \Kiniauth\Objects\Security\User
     */
    public function changeUserEmail($newEmailAddress, $password, $userId = User::LOGGED_IN_USER) {
        return $this->userService->changeUserEmail($newEmailAddress, $password, $userId);
    }

    /**
     * Change the logged in user backup email
     *
     * @http GET /changeBackupEmail
     *
     * @param $newEmailAddress
     * @param $password
     * @return \Kiniauth\Objects\Security\User
     */
    public function changeUserBackupEmail($newEmailAddress, $password, $userId = User::LOGGED_IN_USER) {
        return $this->userService->changeUserBackupEmail($newEmailAddress, $password, $userId);
    }

    /**
     * Change the logged in user mobile number
     *
     * @http GET /changeMobile
     *
     * @param $newMobile
     * @param $password
     * @return \Kiniauth\Objects\Security\User
     */
    public function changeUserMobile($newMobile, $password, $userId = User::LOGGED_IN_USER) {
        return $this->userService->changeUserMobile($newMobile, $password, $userId);
    }

    /**
     * Generate two factor settings
     *
     * @http GET /twoFactorSettings
     *
     * @return array
     */
    public function createTwoFactorSettings($userId = User::LOGGED_IN_USER) {
        return $this->userService->generateTwoFactorSettings($userId);
    }

    /**
     * @http GET /newTwoFactor
     *
     * @param $code
     * @param $secret
     * @return bool|\Kiniauth\Objects\Security\User
     */
    public function authenticateNewTwoFactorCode($code, $secret, $userId = User::LOGGED_IN_USER) {
        return $this->userService->authenticateNewTwoFactor($code, $secret, $userId);
    }

    /**
     * Disable the current logged in users two fa.
     *
     * @http GET /disableTwoFA
     *
     * @return \Kiniauth\Objects\Security\User
     */
    public function disableTwoFactor($userId = User::LOGGED_IN_USER) {
        return $this->userService->disableTwoFactor($userId);
    }

    /**
     * Search for account users
     *
     * @http GET /search
     *
     * @param string $searchString
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function searchForAccountUsers($searchString = "", $offset = 0, $limit = 10) {
        $account = $this->session->__getLoggedInAccount()->getAccountId();
        return $this->userService->searchForUsers($searchString, $offset, $limit, $account);
    }

    /**
     * Get all account roles for a user
     *
     * @http GET /roles
     *
     * @hasPrivilege ACCOUNT:*
     * @param $userId
     * @return array
     */
    public function getAllUserAccountRoles($userId) {
        return $this->roleService->getAllUserAccountRoles($userId);
    }

}