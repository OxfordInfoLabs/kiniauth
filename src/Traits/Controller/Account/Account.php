<?php

namespace Kiniauth\Traits\Controller\Account;

use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Account\AccountService;
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
}
