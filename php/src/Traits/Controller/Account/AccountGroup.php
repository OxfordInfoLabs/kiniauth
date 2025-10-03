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
     * @return \Kiniauth\Objects\Account\AccountGroup[]
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
    public function removeAccountFromAccountGroup(int $accountGroupId, int $accountId) {
        $this->accountGroupService->removeMemberFromAccountGroup($accountGroupId, $accountId);
    }

    /**
     * @http DELETE /
     *
     * @param int $accountGroupId
     * @return void
     */
    public function deleteAccountGroup(int $accountGroupId) {
        $this->accountGroupService->deleteAccountGroup($accountGroupId);
    }

    /**
     * Invite an account to an account group
     *
     * @http GET /invite
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
     * Get the details for an invitation using an invitation code.
     *
     * @http GET /invitation/$invitationCode
     *
     * @param string $invitationCode
     * 
     * @return AccountGroupInvitation
     */
    public function getInvitationDetails($invitationCode) {
        return $this->accountGroupService->getInvitationDetails($invitationCode);
    }

    /**
     * Accept an invitation to join an account group
     *
     * @http POST /invitation/$invitationCode
     *
     * @param string $invitationCode
     *
     * @return void
     */
    public function acceptInviteToAccountGroup($invitationCode) {
        $this->accountGroupService->acceptAccountGroupInvitation($invitationCode);
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