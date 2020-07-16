<?php

namespace Kiniauth\Traits\Controller\Admin;

use Kiniauth\Objects\Account\AccountSummary;
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
}
