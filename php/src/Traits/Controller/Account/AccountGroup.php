<?php

namespace Kiniauth\Traits\Controller\Account;

use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Account\AccountGroupService;
use Kiniauth\ValueObjects\Account\AccountDiscoveryItem;
use Kiniauth\ValueObjects\Account\AccountGroupDescriptor;
use Kiniauth\ValueObjects\Account\AccountGroupInvitation;
use Kiniauth\ValueObjects\Account\AccountInvitation;
use Kiniauth\ValueObjects\Registration\NewUserAccountDescriptor;

trait AccountGroup {

    private $accountGroupService;

    /**
     * Account constructor.
     * @param AccountGroupService $accountGroupService
     */
    public function __construct($accountGroupService) {
        $this->accountGroupService = $accountGroupService;
    }

    /**
     * Get an account group
     *
     * @http GET /
     *
     * @param int $accountGroupId
     *
     * @return \Kiniauth\Objects\Account\AccountGroup
     *
     */
    public function getAccountGroup($accountGroupId) {
        return AccountSummary::fetch($accountGroupId);
    }

    /**
     * Create a new account group
     *
     * @http POST /new
     *
     * @param AccountGroupDescriptor $accountGroupDescriptor
     */
    public function createAccountGroup($accountGroupDescriptor) {
        return $this->accountGroupService->createAccountGroup($accountGroupDescriptor);
    }

    /**
     * Get a user object by userId (optional), defaults to the logged in user
     *
     * @http GET /members
     *
     * @param int $accountGroupId
     *
     * @return AccountGroupMember[]
     */
    public function getAccountGroupMembers($accountGroupId) {
        return $this->accountGroupService->getMembersOfAccountGroup($accountGroupId);
    }

     /**
     * @http GET /removeMember
     *
     * @param int $accountGroupId
     * @param int $accountId
     */
    public function removeUserFromAccountGroup(int $accountGroupId, int $accountId) {
        $this->accountGroupService->removeMemberFromAccountGroup($accountGroupId, $accountId);
    }

    /**
     * Invite an account to an account group
     *
     * @http POST /invite
     *
     * @param int $accountGroupId
     * @param int $accountId
     */
    public function inviteAccountToAccountGroup($accountGroupId, $accountId) {
        $this->accountGroupService->inviteAccountToAccountGroup($accountGroupId, $accountId);
    }


    /**
     * Get invitations on account group
     *
     * @http GET /invitations
     *
     * @param int $accountGroupId
     *
     * @return AccountGroupInvitation[]
     */
    public function getActiveAccountGroupInvitationAccounts($accountGroupId) {
        return $this->accountGroupService->getActiveAccountGroupInvitationAccounts($accountGroupId);
    }

    /**
     * Resend an invitation to an account group
     *
     * @http PUT /invite
     *
     * @param int $accountGroupId
     * @param int $accountId
     * @return void
     */
    public function resendActiveAccountInvitationEmail($accountGroupId, $accountId) {
        $this->accountGroupService->resendAccountGroupInvitationEmail($accountGroupId, $accountId);
    }

    /**
     * Revoke an invitation to an account group
     *
     * @param $accountGroupId
     * @param $accountId
     * @return void
     */
    public function revokeAccountGroupInvitation($accountGroupId, $accountId) {
        $this->accountGroupService->revokeAccountGroupInvitation($accountGroupId, $accountId);
    }
}