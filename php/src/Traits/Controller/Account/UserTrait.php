<?php

namespace Kiniauth\Traits\Controller\Account;

use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserSummary;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\RoleService;
use Kiniauth\ValueObjects\Security\ScopeObjectRolesAssignment;
use Kiniauth\ValueObjects\Security\UserExtended;


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
     * @http POST /changeDetails
     *
     * @param $payload
     * @param string $userId
     * @return boolean
     */
    public function changeUserDetails($payload, $userId = User::LOGGED_IN_USER) {
        return $this->userService->changeUserDetails($payload["newEmailAddress"], $payload["newName"], $payload["password"], $userId);
    }


    /**
     * Update the name of the logged-in user
     *
     * @http POST /changeName
     *
     * @param mixed $payload
     * @return bool
     */
    public function changeUserName($payload) {
        return $this->userService->changeUserName($payload["newName"], $payload["password"]);
    }

    /**
     * Change the logged-in users email
     *
     * @http GET /changeEmail
     *
     * @param mixed $payload
     * @param string $userId
     * @return boolean
     */
    public function changeUserEmail($payload, $userId = User::LOGGED_IN_USER) {
        return $this->userService->changeUserEmail($payload["newEmailAddress"], $payload["password"], $payload["hashedPassword"] ?? null, $userId);
    }


    /**
     * Change user password
     *
     * @http POST /changeUserPassword
     *
     * @param mixed $payload
     */
    public function changeUserPassword($payload) {
        $this->userService->changeUserPassword($payload["newPassword"], $payload["password"]);
    }


    /**
     * Change the logged-in user backup email
     *
     * @http POST /changeBackupEmail
     *
     * @param mixed $payload
     * @param string $userId
     * @return boolean
     */
    public function changeUserBackupEmail($payload, $userId = User::LOGGED_IN_USER) {
        return $this->userService->changeUserBackupEmail($payload["newEmailAddress"], $payload["password"], $userId);
    }

    /**
     * Change the logged-in user mobile number
     *
     * @http POST /changeMobile
     *
     * @param mixed $payload
     * @param string $userId
     * @return boolean
     */
    public function changeUserMobile($payload, $userId = User::LOGGED_IN_USER) {
        return $this->userService->changeUserMobile($payload["newMobile"], $payload["password"], $userId);
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
        return $this->roleService->getAllAccountRoles(Role::APPLIES_TO_USER, $userId);
    }

    /**
     * Search for account users
     *
     * @http GET /search
     *
     * @param string $searchString
     * @param int $offset
     * @param int $limit
     * @param int $accountId
     * @return array
     */
    public function searchForAccountUsers($searchString = "", $offset = 0, $limit = 10, $accountId = \Kiniauth\Objects\Account\Account::LOGGED_IN_ACCOUNT) {
        return $this->userService->searchForUsers($searchString, $offset, $limit, $accountId);
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
        return $this->roleService->getFilteredAssignableAccountScopeRoles(Role::APPLIES_TO_USER, $userId, $scope, $filterString, $offset, $limit);
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
        $this->roleService->updateAssignedScopeObjectRoles(Role::APPLIES_TO_USER, $userId, $scopeObjectRolesAssignments);
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

    /**
     * Unlock a user account
     *
     * @http GET /unlock
     *
     * @param $userId
     */
    public function unlockUser($userId) {
        $this->userService->unlockUserByUserId($userId);
    }

    /**
     * Suspend a user
     *
     * @http GET /suspend
     *
     * @param $userId
     */
    public function suspendUser($userId) {
        $this->userService->suspendUser($userId);
    }
}
