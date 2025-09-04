<?php

namespace Kiniauth\Traits\Controller\Admin;

use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\ValueObjects\Account\AccountInvitation;
use Kiniauth\ValueObjects\Registration\NewUserAccountDescriptor;

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

    /**
     * Remove a user from account
     *
     * @http GET /removeUser
     *
     * @param string $accountId
     * @param int $userId
     */
    public function removeUserFromAccount($accountId, $userId) {
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

    /**
     * @http PUT /invite
     *
     * @param string $emailAddress
     * @param int $accountId
     * @return void
     */
    public function resendActiveAccountInvitationEmail($emailAddress, $accountId) {
        $this->accountService->resendActiveAccountInvitationEmail($emailAddress, $accountId);
    }

    /**
     * Get active account invitation email addresses.
     *
     * @http GET /invitations
     *
     * @param int $accountId
     *
     * @return AccountInvitation[]
     */
    public function getAccountInvitationEmailAddresses($accountId) {
        return $this->accountService->getActiveAccountInvitationEmailAddresses($accountId);
    }

    /**
     * Get account security domains
     *
     * @http GET /$accountId/securityDomains
     *
     * @return string[]
     */
    public function getAccountSecurityDomains($accountId) {
        return $this->accountService->getSecurityDomains($accountId);
    }

    /**
     * @http PUT /$accountId/securityDomains
     *
     * @param int $accountId
     * @param array $securityDomains
     *
     * @return void
     */
    public function updateSecurityDomains($accountId, $securityDomains = []) {
        $this->accountService->updateSecurityDomains($securityDomains, $accountId);
    }

}
