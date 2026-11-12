<?php

namespace Kiniauth\Traits\Controller\Account;

use Kiniauth\Exception\Security\AccountAlreadyAttachedToAccountGroupException;
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

    private $accountGroupService;

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
     * @param int $loggedInAccountId
     *
     * @return \Kiniauth\Objects\Account\AccountGroup
     * @throws AccessDeniedException
     */
    public function getAccountGroup(
        int $accountGroupId,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): \Kiniauth\Objects\Account\AccountGroup {
        return $this->accountGroupService->getAccountGroup($accountGroupId, $loggedInAccountId);
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
     * @param int $loggedInAccountId
     *
     * @return AccountGroupMember[]
     * @throws AccessDeniedException
     */
    public function getAccountGroupMembers(
        int $accountGroupId,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): array {
        return $this->accountGroupService->getMembersOfAccountGroup($accountGroupId, $loggedInAccountId);
    }

    /**
     * @http GET /removeMember
     *
     * @param int $accountGroupId
     * @param int $removingAccountId
     * @param int $loggedInAccountId
     *
     * @return void
     * @throws AccessDeniedException
     */
    public function removeAccountFromAccountGroup(
        int $accountGroupId,
        int $removingAccountId,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): void {
        $this->accountGroupService->removeMemberFromAccountGroup(
            $accountGroupId,
            $removingAccountId,
            $loggedInAccountId
        );
    }

    /**
     * @http GET /leave
     *
     * @param int $accountGroupId
     * @param int $loggedInAccountId
     *
     * @return void
     * @throws AccessDeniedException
     */
    public function leaveAccountGroup(
        int $accountGroupId,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): void {
        $this->accountGroupService->leaveAccountGroup($accountGroupId, $loggedInAccountId);
    }

    /**
     * @http DELETE /
     *
     * @param int $accountGroupId
     * @param int $loggedInAccountId
     *
     * @return void
     * @throws AccessDeniedException|InvalidAccountGroupOwnerException
     */
    public function deleteAccountGroup(
        int $accountGroupId,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): void {
        $this->accountGroupService->deleteAccountGroup($accountGroupId, $loggedInAccountId);
    }

    /**
     * Invite an account to an account group
     *
     * @http GET /invite
     *
     * @param int       $accountGroupId
     * @param string    $accountExternalIdentifier
     * @param int       $loggedInAccountId
     *
     * @return void
     * @throws InvalidAccountGroupOwnerException|AccountAlreadyAttachedToAccountGroupException|Throwable
     */
    public function inviteAccountToAccountGroup(
        int $accountGroupId,
        string $accountExternalIdentifier,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): void {
        $this->accountGroupService->inviteAccountToAccountGroup(
            $accountGroupId,
            $accountExternalIdentifier,
            $loggedInAccountId
        );
    }


    /**
     * Get invitations on account group
     *
     * @http GET /invitations
     *
     * @param int $accountGroupId
     * @param int $loggedInAccountId
     *
     * @return AccountGroupInvitation[]
     * @throws AccessDeniedException|InvalidAccountGroupOwnerException
     */
    public function getActiveAccountGroupInvitationAccounts(
        int $accountGroupId,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): array {
        return $this->accountGroupService->getActiveAccountGroupInvitationAccounts(
            $accountGroupId,
            $loggedInAccountId
        );
    }

    /**
     * Get the details for an invitation using an invitation code.
     *
     * @http GET /invitation/$invitationCode
     *
     * @param string $invitationCode
     * @param int $loggedInAccountId
     *
     * @return AccountGroupInvitation
     * @throws ValidationException|AccessDeniedException
     */
    public function getInvitationDetails(
        string $invitationCode,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): AccountGroupInvitation {
        return $this->accountGroupService->getInvitationDetails($invitationCode, $loggedInAccountId);
    }

    /**
     * Accept an invitation to join an account group
     *
     * @http POST /invitation/$invitationCode
     *
     * @param string    $invitationCode
     * @param int       $loggedInAccountId
     *
     * @return void
     * @throws AccessDeniedException|Throwable|ValidationException
     */
    public function acceptInviteToAccountGroup(
        string $invitationCode,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): void {
        $this->accountGroupService->acceptAccountGroupInvitation($invitationCode, $loggedInAccountId);
    }

    /**
     * Revoke an invitation to an account group
     *
     * @http DELETE /invite
     *
     * @param AccountGroupInvitation $invite
     * @param int $loggedInAccountId
     *
     * @return void
     * @throws AccessDeniedException|InvalidAccountGroupOwnerException
     */
    public function revokeAccountGroupInvitation(
        AccountGroupInvitation $invite,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): void {
        $this->accountGroupService->revokeAccountGroupInvitation($invite, $loggedInAccountId);
    }
}