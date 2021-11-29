<?php

namespace Kiniauth\Traits\Controller\Admin;

use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Services\Account\AccountService;
use Kiniauth\ValueObjects\Registration\NewUserAccountDescriptor;
use Kiniauth\ValueObjects\Registration\NewUserDescriptor;

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
     * Create a new account
     *
     * @http POST /
     *
     * @param NewUserAccountDescriptor $newUserAccountDescriptor
     */
    public function createAccount($newUserAccountDescriptor) {
        return $this->accountService->createAccount($newUserAccountDescriptor->getAccountName(), $newUserAccountDescriptor->getEmailAddress(),
            $newUserAccountDescriptor->getPassword(), $newUserAccountDescriptor->getName());
    }


}
