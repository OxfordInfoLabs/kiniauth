<?php

namespace Kiniauth\Traits\Controller\Account;

use Exception;
use Kiniauth\Exception\Security\AccountAlreadyAttachedToAccountGroupException;
use Kiniauth\Exception\Security\AccountGroupOwnerCannotLeaveException;
use Kiniauth\Exception\Security\InvalidAccountGroupOwnerException;
use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Services\Account\AccountGroupService;
use Kiniauth\ValueObjects\Account\AccountGroupDescriptor;
use Kiniauth\ValueObjects\Account\AccountGroupInvitation;
use Kiniauth\Objects\Account\Account;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Validation\ValidationException;
use Throwable;

trait AccountGroup {

    /**
     * @var AccountGroupService
     */
    private AccountGroupService $accountGroupService;

    /**
     * Account Controller constructor.
     * @param AccountGroupService $accountGroupService
     */
    public function __construct(AccountGroupService $accountGroupService) {
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
     */
    public function getAccountGroup(int $accountGroupId): \Kiniauth\Objects\Account\AccountGroup {
        return $this->accountGroupService->getAccountGroup($accountGroupId);
    }

    /**
     * @http GET /list
     *
     * @param int $accountId
     *
     * @return \Kiniauth\Objects\Account\AccountGroup[]
     */
    public function listAccountGroup($accountId = Account::LOGGED_IN_ACCOUNT): array {
        return $this->accountGroupService->listAccountGroupsForAccount($accountId);
    }

    /**
     * Create a new account group
     *
     * @http POST /new
     *
     * @param AccountGroupDescriptor $accountGroupDescriptor
     * @return int
     */
    public function createAccountGroup(AccountGroupDescriptor $accountGroupDescriptor): int {
        return $this->accountGroupService->createAccountGroup($accountGroupDescriptor);
    }

    /**
     * Get a user object by userId (optional), defaults to the logged-in user
     *
     * @http GET /members
     *
     * @param int $accountGroupId
     *
     * @return AccountGroupMember[]
     */
    public function getAccountGroupMembers(int $accountGroupId): array {
        return $this->accountGroupService->getMembersOfAccountGroup($accountGroupId);
    }

    /**
     * @http GET /removeMember
     *
     * @param int $accountGroupId
     * @param int $removingAccountId
     *
     * @return void
     * @throws AccessDeniedException|Exception
     */
    public function removeAccountFromAccountGroup(int $accountGroupId, int $removingAccountId): void {
        $this->accountGroupService->removeMemberFromAccountGroup($accountGroupId, $removingAccountId);
    }

    /**
     * @http GET /leave
     *
     * @param int $accountGroupId
     *
     * @return void
     * @throws AccountGroupOwnerCannotLeaveException
     */
    public function leaveAccountGroup(int $accountGroupId): void {
        $this->accountGroupService->leaveAccountGroup($accountGroupId);
    }

    /**
     * @http DELETE /
     *
     * @param int $accountGroupId
     *
     * @return void
     */
    public function deleteAccountGroup(int $accountGroupId): void {
        $this->accountGroupService->deleteAccountGroup($accountGroupId);
    }

    /**
     * Invite an account to an account group
     *
     * @http GET /invite
     *
     * @param int       $accountGroupId
     * @param string    $accountExternalIdentifier
     *
     * @return void
     * @throws InvalidAccountGroupOwnerException|AccountAlreadyAttachedToAccountGroupException|Throwable
     */
    public function inviteAccountToAccountGroup(int $accountGroupId, string $accountExternalIdentifier): void {
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
     * @throws AccessDeniedException|InvalidAccountGroupOwnerException
     */
    public function getActiveAccountGroupInvitationAccounts(int $accountGroupId): array {
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
     * @throws ValidationException|AccessDeniedException
     */
    public function getInvitationDetails(string $invitationCode): AccountGroupInvitation {
        return $this->accountGroupService->getInvitationDetails($invitationCode);
    }

    /**
     * Accept an invitation to join an account group
     *
     * @http POST /invitation/$invitationCode
     *
     * @param string    $invitationCode
     *
     * @return void
     * @throws AccessDeniedException|Throwable|ValidationException
     */
    public function acceptInviteToAccountGroup(string $invitationCode): void {
        $this->accountGroupService->acceptAccountGroupInvitation($invitationCode);
    }

    /**
     * Revoke an invitation to an account group
     *
     * @http DELETE /invite
     *
     * @param AccountGroupInvitation $invite
     *
     * @return void
     * @throws InvalidAccountGroupOwnerException
     */
    public function revokeAccountGroupInvitation(AccountGroupInvitation $invite): void {
        $this->accountGroupService->revokeAccountGroupInvitation($invite);
    }
}