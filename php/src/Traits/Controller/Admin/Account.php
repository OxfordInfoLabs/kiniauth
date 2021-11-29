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
     * @http GET /$accountId
     *
     * @return AccountSummary
     *
     */
    public function getAccount($accountId) {
        return AccountSummary::fetch($accountId);
    }

    /**
     * Search for accounts limiting to search string optionally
     *
     * @http GET /
     *
     * @param string $searchString
     * @param int $offset
     * @param int $limit
     */
    public function searchForAccounts($searchString = "", $offset = 0, $limit = 10) {
        return $this->accountService->searchForAccounts($searchString, $offset, $limit);
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


    /**
     * Update an account name
     *
     * @http PUT /$accountId/name
     *
     * @oaram int $accountId
     * @param string $newAccountName
     */
    public function updateAccountName($accountId, $newAccountName) {
        $this->accountService->changeAccountName($newAccountName, null, $accountId);
    }


    /**
     * Suspend an account
     *
     * @http PUT /$accountId/suspend
     *
     * @param int $accountId
     * @param string $note
     */
    public function suspendAccount($accountId, $note) {
        $this->accountService->suspendAccount($accountId, $note);
    }

    /**
     * Suspend an account
     *
     * @http PUT /$accountId/reactivate
     *
     * @param int $accountId
     * @param string $note
     */
    public function reactivateAccount($accountId, $note) {
        $this->accountService->reactivateAccount($accountId, $note);
    }

}
