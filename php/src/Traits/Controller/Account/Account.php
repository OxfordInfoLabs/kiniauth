<?php

namespace Kiniauth\Traits\Controller\Account;

use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Security\RoleService;

trait Account {


    private $accountService;

    private $roleService;

    /**
     * Account constructor.
     * @param \Kiniauth\Services\Account\AccountService $accountService
     * @param RoleService $roleService
     */
    public function __construct($accountService, $roleService) {
        $this->accountService = $accountService;
        $this->roleService = $roleService;
    }

    /**
     * Get an account defaulting to logged in account
     *
     * @http GET /
     *
     * @return AccountSummary
     *
     */
    public function getAccount($accountId = \Kiniauth\Objects\Account\Account::LOGGED_IN_ACCOUNT) {
        return AccountSummary::fetch($accountId);
    }

    /**
     * Update the name of the logged-in user
     *
     * @http POST /changeName
     *
     * @param mixed $payload
     * @return bool
     */
    public function changeAccountName($payload) {
        return $this->accountService->changeAccountName($payload["newName"], $payload["password"]);
    }


    /**
     * Get account settings
     *
     * @http GET /settings
     */
    public function getAccountSettings() {
        return $this->accountService->getAccountSettings();
    }

    /**
     * Update account settings with a new full set
     *
     * @http PUT /settings
     *
     * @param mixed $settings
     */
    public function updateAccountSettings($settings) {
        $this->accountService->updateAccountSettings($settings);
    }


    /**
     * Get a user object by userId (optional), defaults to the logged in user
     *
     * @http GET /user
     *
     * @param string $userId
     *
     * @return User
     */
    public function getUser($userId = User::LOGGED_IN_USER) {
        return User::fetch($userId);
    }

    /**
     * @http GET /possibleRoles
     *
     * @return mixed
     */
    public function getAllPossibleAccountScopeRoles() {
        return $this->roleService->getAllPossibleAccountScopeRoles();
    }

    /**
     * Remove a user from account
     *
     * @http GET /removeUser
     *
     * @param $userId
     * @param string $accountId
     */
    public function removeUserFromAccount($userId, $accountId = \Kiniauth\Objects\Account\Account::LOGGED_IN_ACCOUNT) {
        $this->accountService->removeUserFromAccount($accountId, $userId);
    }

    /**
     * Invite a user to the logged in account
     *
     * @http POST /invite
     *
     * @param $emailAddress
     * @param $initialAssignedRoles
     * @param string $accountId
     * @throws \Kiniauth\Exception\Security\UserAlreadyAttachedToAccountException
     */
    public function inviteUserToAccount($initialAssignedRoles, $emailAddress, $accountId = \Kiniauth\Objects\Account\Account::LOGGED_IN_ACCOUNT) {
        $this->accountService->inviteUserToAccount($accountId, $emailAddress, $initialAssignedRoles);
    }
}
