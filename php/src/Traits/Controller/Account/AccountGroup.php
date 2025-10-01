<?php

namespace Kiniauth\Traits\Controller\Account;

use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Services\Account\AccountGroupService;
use Kiniauth\ValueObjects\Account\AccountGroupDescriptor;
use Kiniauth\ValueObjects\Account\AccountGroupInvitation;

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
        return $this->accountGroupService->getAccountGroup($accountGroupId);
    }

    /**
     * @http GET /list
     *
     * @param $accountId
     * @return AccountGroup[]
     */
    public function listAccountGroup($accountId = \Kiniauth\Objects\Account\Account::LOGGED_IN_ACCOUNT) {
        return $this->accountGroupService->listAccountGroupsForAccount($accountId);
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
     * @param string $accountExternalIdentifier
     */
    public function inviteAccountToAccountGroup($accountGroupId, $accountExternalIdentifier) {
        $this->accountGroupService->inviteAccountToAccountGroup($accountGroupId, $accountExternalIdentifier);
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
     * Revoke an invitation to an account group
     *
     * @http DELETE /invite
     *
     * @param AccountGroupInvitation $invite
     * @return void
     */
    public function revokeAccountGroupInvitation($invite) {
        $this->accountGroupService->revokeAccountGroupInvitation($invite);
    }
}