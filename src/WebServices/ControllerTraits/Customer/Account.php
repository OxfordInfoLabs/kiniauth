<?php

namespace Kiniauth\WebServices\ControllerTraits\Customer;

use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Account\AccountService;

trait Account {


    private $accountService;

    /**
     * Account constructor.
     * @param \Kiniauth\Services\Account\AccountService $accountService
     */
    public function __construct($accountService) {
        $this->accountService = $accountService;
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
}
