<?php

namespace Kiniauth\Traits\Controller\Account;

use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\RoleService;
use Kiniauth\ValueObjects\Account\AccountDiscoveryItem;
use Kiniauth\ValueObjects\Account\AccountInvitation;
use Kiniauth\ValueObjects\Registration\NewUserAccountDescriptor;
use Kinikit\Core\Logging\Logger;

trait Account {


    private $accountService;

    private $roleService;

    private $session;

    /**
     * Account constructor.
     * @param \Kiniauth\Services\Account\AccountService $accountService
     * @param RoleService $roleService
     * @param Session $session
     */
    public function __construct($accountService, $roleService, $session) {
        $this->accountService = $accountService;
        $this->roleService = $roleService;
        $this->session = $session;
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
     * Search for accounts limiting to search string optionally
     *
     * @http GET /subAccounts
     *
     * @param string $searchString
     * @param int $offset
     * @param int $limit
     */
    public function searchForSubAccounts($searchString = "", $offset = 0, $limit = 10) {
        return $this->accountService->searchForAccounts($searchString, $offset, $limit, $this->session->__getLoggedInAccount()->getAccountId());
    }

    /**
     * Create a new account
     *
     * @http POST /subAccount
     *
     * @param NewUserAccountDescriptor $newUserAccountDescriptor
     */
    public function createSubAccount($newUserAccountDescriptor) {
        return $this->accountService->createAccount($newUserAccountDescriptor->getAccountName(), $newUserAccountDescriptor->getEmailAddress(),
            $newUserAccountDescriptor->getPassword(), $newUserAccountDescriptor->getName(), $this->session->__getLoggedInAccount()->getAccountId());
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
     * Update the logo for the logged in user
     *
     * @http POST /changeLogo
     *
     * @param $logo
     * @return bool
     *
     */
    public function changeAccountLogo($logo) {
        return $this->accountService->updateLogo($logo);
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
     * Get account discovery settings
     *
     * @http GET /discovery
     *
     * @return AccountDiscoveryItem
     */
    public function getAccountDiscoverySettings() {
        return $this->accountService->getAccountDiscoverySettings();
    }


    /**
     * Get account security domains
     *
     * @http GET /securityDomains
     *
     * @return string[]
     */
    public function getAccountSecurityDomains() {
        return $this->accountService->getSecurityDomains();
    }


    /**
     * Set discoverability for an account
     *
     * @http PUT /discoverable
     *
     * @param mixed $discoverable
     */
    public function setAccountDiscoverable($discoverable = false) {
        $this->accountService->setAccountDiscoverable($discoverable);
    }


    /**
     * Search for discoverable accounts
     *
     * @http GET /discoverable
     *
     * @param $searchTerm
     * @param $offset
     * @param $limit
     *
     * @return AccountDiscoveryItem[]
     */
    public function searchForDiscoverableAccounts($searchTerm, $offset = 0, $limit = 25) {
        return $this->accountService->searchForDiscoverableAccounts($searchTerm, $offset, $limit);
    }


    /**
     * Lookup discoverable account by external identifier
     *
     * @http GET /discoverable/$externalIdentifier
     *
     * @param $externalIdentifier
     *
     * @return AccountDiscoveryItem
     * @throws \Kinikit\Core\Exception\ItemNotFoundException
     */
    public function lookupDiscoverableAccountByExternalIdentifier($externalIdentifier) {
        return $this->accountService->lookupDiscoverableAccountByExternalIdentifier($externalIdentifier);
    }


    /**
     * Generate account external identifier
     *
     * @http PUT /externalIdentifier
     *
     * @return string
     */
    public function generateAccountExternalIdentifier() {
        return $this->accountService->generateAccountExternalIdentifier();
    }


    /**
     * Unset account external identifier
     *
     * @http DELETE /externalIdentifier
     *
     * @return string
     */
    public function unsetAccountExternalIdentifier() {
        $this->accountService->unsetAccountExternalIdentifier();
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


    /**
     * Get active account invitation email addresses.
     *
     * @http GET /invitations
     *
     * @return AccountInvitation[]
     */
    public function getActiveAccountInvitationEmailAddresses() {
        return $this->accountService->getActiveAccountInvitationEmailAddresses();
    }

    /**
     * @http PUT /invite
     *
     * @param string $emailAddress
     * @return null
     */
    public function resendActiveAccountInvitationEmail($emailAddress) {
        $this->accountService->resendActiveAccountInvitationEmail($emailAddress);
    }


}
