<?php

namespace Kiniauth\Traits\Controller\Account;

use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserSummary;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\RoleService;
use Kiniauth\ValueObjects\Security\ScopeObjectRolesAssignment;
use Kiniauth\ValueObjects\Security\UserExtended;
use Kinikit\Core\Logging\Logger;

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
     * @return UserExtended
     */
    public function getUser($userId = User::LOGGED_IN_USER) {
        $user = User::fetch($userId);
        return new UserExtended($user);
    }

    /**
     * Get a user object by userId (optional), defaults to the logged in user
     *
     * @http GET /summary
     *
     * @param string $userId
     *
     * @return UserSummary
     */
    public function getUserSummary($userId = User::LOGGED_IN_USER) {
        return UserSummary::fetch($userId);
    }

    /**
     * Update the email and name for the supplied user
     *
     * @http GET /changeDetails
     *
     * @param $newEmailAddress
     * @param $newName
     * @param $password
     * @param string $userId
     * @return boolean
     */
    public function changeUserDetails($newEmailAddress, $newName, $password, $userId = User::LOGGED_IN_USER) {
        return $this->userService->changeUserDetails($newEmailAddress, $newName, $password, $userId);
    }


    /**
     * Update the name of the logged in user
     *
     * @http GET /changeName
     *
     * @param $newName
     * @param $password
     * @return bool
     */
    public function changeUserName($newName, $password) {
        return $this->userService->changeUserName($newName, $password);
    }

    /**
     * Change the logged in users email
     *
     * @http GET /changeEmail
     *
     * @param $newEmailAddress
     * @param $password
     * @param null $hashedPassword
     * @param string $userId
     * @return boolean
     */
    public function changeUserEmail($newEmailAddress, $password, $hashedPassword = null, $userId = User::LOGGED_IN_USER) {
        return $this->userService->changeUserEmail($newEmailAddress, $password, $hashedPassword, $userId);
    }

    /**
     * Change the logged in user backup email
     *
     * @http GET /changeBackupEmail
     *
     * @param $newEmailAddress
     * @param $password
     * @param string $userId
     * @return boolean
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
     * @param string $userId
     * @return boolean
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
     * Update the passed settings
     *
     * @http PUT /applicationSettings
     *
     * @param mixed $newSettings
     */
    public function updateApplicationSettings($newSettings) {
        $this->userService->updateUserApplicationSettings($newSettings);
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
     * Get all filtered assignable account scope roles
     *
     * @http GET /assignableRoles
     *
     * @param $userId
     * @param $filterString
     * @param $offset
     * @param $limit
     */
    public function getFilteredAssignableAccountScopeRoles($userId, $scope, $filterString = "", $offset = 0, $limit = 10) {
        return $this->roleService->getFilteredUserAssignableAccountScopeRoles($userId, $scope, $filterString, $offset, $limit);
    }

    /**
     * Update the roles for a user scope
     *
     * @http POST /updateUserScope
     *
     * @param ScopeObjectRolesAssignment[] $scopeObjectRolesAssignments
     * @param string $userId
     */
    public function updateAssignedScopeObjectRolesForUser($scopeObjectRolesAssignments, $userId) {
        $this->roleService->updateAssignedScopeObjectRolesForUser($userId, $scopeObjectRolesAssignments);
    }

    /**
     * Return the accounts this user is associated with
     *
     * @http GET /accounts
     *
     * @param string $userId
     * @return mixed
     */
    public function getUserAccounts($userId = User::LOGGED_IN_USER) {
        return $this->userService->getUserAccounts($userId);
    }

    /**
     * Switch accounts for the user
     *
     * @http GET /switchAccount
     *
     * @param $accountId
     * @param string $userId
     * @throws \Kiniauth\Exception\Security\InvalidAccountForUserException
     */
    public function switchActiveAccount($accountId, $userId = User::LOGGED_IN_USER) {
        $this->userService->switchActiveAccount($accountId, $userId);
    }
}
