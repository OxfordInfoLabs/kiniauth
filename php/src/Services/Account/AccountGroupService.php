<?php

namespace Kiniauth\Services\Account;

use Kiniauth\Exception\Security\AccountAlreadyAttachedToAccountGroupException;
use Kiniauth\Exception\Security\InvalidAccountGroupOwnerException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountGroup;
use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Objects\Account\AccountLabel;
use Kiniauth\Objects\Communication\Email\AccountTemplatedEmail;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\ValueObjects\Account\AccountGroupDescriptor;
use Kiniauth\ValueObjects\Account\AccountGroupInvitation;
use Kinikit\Core\Communication\Email\MissingEmailTemplateException;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Core\Logging\Logger;

class AccountGroupService {

    /**
     * @var EmailService
     */
    private EmailService $emailService;

    /**
     * @var PendingActionService
     */
    private PendingActionService $pendingActionService;


    /**
     * @var ActiveRecordInterceptor
     */
    private ActiveRecordInterceptor $activeRecordInterceptor;


    /**
     * @var AccountService
     */
    private AccountService $accountService;


    /**
     * @param EmailService $emailService
     * @param PendingActionService $pendingActionService
     */
    public function __construct(EmailService            $emailService, PendingActionService $pendingActionService,
                                ActiveRecordInterceptor $activeRecordInterceptor,
                                AccountService          $accountService) {
        $this->emailService = $emailService;
        $this->pendingActionService = $pendingActionService;
        $this->activeRecordInterceptor = $activeRecordInterceptor;
        $this->accountService = $accountService;
    }


    /**
     * @param int $accountGroupId
     * @return AccountGroup
     */
    public function getAccountGroup(int $accountGroupId): AccountGroup {
        return AccountGroup::fetch($accountGroupId);
    }


    /**
     * @param array $accountGroupIds
     * @return AccountGroup[]
     */
    public function getMultipleAccountGroups(array $accountGroupIds): array {
        return AccountGroup::multiFetch($accountGroupIds);
    }

    /**
     * @return AccountGroup[]
     */
    public function getAllAccountGroups(): array {
        $accountGroups = AccountGroup::filter();
        return $accountGroups;
    }

    /**
     * @param int $accountId
     * @return AccountGroup[]
     */
    public function listAccountGroupsForAccount(int $accountId): array {
        /** @var AccountGroupMember[] $accountGroupMembers */
        $accountGroupMembers = AccountGroupMember::filter("WHERE member_account_id = ?", $accountId);

        $accountGroupIds = array_map(fn($accountGroupMember) => $accountGroupMember->getAccountGroupId(), $accountGroupMembers);
        return AccountGroup::multiFetch($accountGroupIds);
    }

    /**
     * @param AccountGroupDescriptor $accountGroupDescriptor
     * @return int
     */
    public function createAccountGroup(AccountGroupDescriptor $accountGroupDescriptor): int {

        $accountId = $accountGroupDescriptor->getOwnerAccountId() ?? Account::LOGGED_IN_ACCOUNT;

        $accountGroup = new AccountGroup(
            $accountGroupDescriptor->getName(),
            $accountGroupDescriptor->getDescription(),
            $accountId,
            [
                new AccountGroupMember(null, $accountId)
            ]
        );
        $accountGroup->save();
        return $accountGroup->getId();
    }

    /**
     * @param int $accountGroupId
     * @return AccountGroupMember[]
     */
    public function getMembersOfAccountGroup(int $accountGroupId): array {
        /** @var AccountGroup $accountGroup */
        $accountGroup = AccountGroup::fetch($accountGroupId);
        return $accountGroup->getAccountGroupMembers();
    }

    /**
     * @param int $accountGroupId
     * @param int $accountId
     * @return void
     */
    public function addMemberToAccountGroup(int $accountGroupId, int $accountId): void {
        // Check if member exists
        try {
            AccountGroupMember::fetch([$accountGroupId, $accountId]);
            return;
        } catch (ObjectNotFoundException) {
            // Great - doesn't already exist
        }

        /** @var AccountGroup $accountGroup */
        $accountGroup = AccountGroup::fetch($accountGroupId);


        $accountGroup->addMember($accountId);
        $accountGroup->save();
    }

    /**
     * @param int $accountGroupId
     * @param int $accountId
     * @return void
     */
    public function removeMemberFromAccountGroup(int $accountGroupId, int $accountId): void {
        try {
            /** @var AccountGroupMember $accountGroupMember */
            $accountGroupMember = AccountGroupMember::fetch([$accountGroupId, $accountId]);
            $accountGroupMember->remove();
        } catch (ObjectNotFoundException $e) {
            return;
        }

    }

    /**
     * Invite an account to join an account group.
     *
     * @param int $accountGroupId
     * @param string $accountExternalIdentifier
     * @param int $loggedInAccountId
     * @return void
     *
     * @objectInterceptorDisabled
     */
    public function inviteAccountToAccountGroup(int $accountGroupId, string $accountExternalIdentifier, $loggedInAccountId = Account::LOGGED_IN_ACCOUNT): void {

        // Get the account group
        $accountGroup = $this->getAccountGroup($accountGroupId);

        // Verify permission
        if ($accountGroup->getOwnerAccountId() !== $loggedInAccountId) {
            throw new InvalidAccountGroupOwnerException();
        }

        // Get the account id for the external identifier
        $accountId = $this->accountService->getAccountByExternalIdentifier($accountExternalIdentifier)->getAccountId();

        // If already a member, return
        try {
            AccountGroupMember::fetch([$accountGroupId, $accountId]);
            throw new AccountAlreadyAttachedToAccountGroupException($accountId);
        } catch (ObjectNotFoundException) {
            // Carry on. This is a new member
        }

        // Create a pending action for the invite
        $invitationCode = $this->pendingActionService->createPendingAction("ACCOUNT_GROUP_INVITE", $accountGroupId, [
            "accountId" => $accountId
        ]);


        // Allow insecure sending of email.
        $this->activeRecordInterceptor->executeInsecure(function () use ($accountId, $accountGroup, $invitationCode) {

            // Send an invitation email attached to the account
            $invitationEmail = new AccountTemplatedEmail($accountId, "security/account-group-invite", [
                "accountGroup" => $accountGroup,
                "invitationCode" => $invitationCode
            ]);

            $this->emailService->send($invitationEmail, $accountId);

        });


    }


    /**
     * @param int $accountGroupId
     * @return AccountGroupInvitation[]
     */
    public function getActiveAccountGroupInvitationAccounts(int $accountGroupId): array {
        $pendingActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("ACCOUNT_GROUP_INVITE", $accountGroupId);
        $accountGroup = $this->getAccountGroup($accountGroupId);
        return array_map(function ($pendingAction) use ($accountGroup) {

            $accountId = $pendingAction->getData()["accountId"] ?? null;

            return new AccountGroupInvitation(
                $accountGroup->getId(),
                $accountGroup->getName(),
                $accountId,
                $accountId ? AccountLabel::fetch($accountId)?->getName() : null,
                $accountGroup->getOwnerAccountId() ? AccountLabel::fetch($accountGroup->getOwnerAccountId())?->getName() : null,
                $pendingAction->getExpiryDateTime()->format("Y-m-d H:i:s")
            );
        }, $pendingActions);
    }


    /**
     * Revoke an invitation
     *
     * @param AccountGroupInvitation $invite
     * @return void
     */
    public function revokeAccountGroupInvitation(AccountGroupInvitation $invite): void {

        $accountGroupId = $invite->getAccountGroupId();
        $accountId = $invite->getAccountId();

        $pendingInvites = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("ACCOUNT_GROUP_INVITE", $accountGroupId) ?? [];

        foreach ($pendingInvites as $invite) {
            if ($invite->getData()["account_id"] == $accountId) {
                $this->pendingActionService->removePendingAction("ACCOUNT_GROUP_INVITE", $invite->getIdentifier());
            }
        }

    }


    /**
     * Get email address associated with an invitation code, or report an issue.
     *
     * @param string $invitationCode
     * @objectInterceptorDisabled
     */
    public function getInvitationDetails(string $invitationCode) {

        try {
            $pendingAction = $this->pendingActionService->getPendingActionByIdentifier("ACCOUNT_GROUP_INVITE", $invitationCode);
            $accountGroup = $this->getAccountGroup($pendingAction->getObjectId());
            $accountId = $pendingAction->getData()["accountId"];
            $requestingAccountId = $accountGroup->getOwnerAccountId();

            return new AccountGroupInvitation(
                $pendingAction->getObjectId(),
                $accountGroup->getName(),
                $accountId,
                $accountId ? AccountLabel::fetch($accountId)?->getName() : null,
                $requestingAccountId ? AccountLabel::fetch($requestingAccountId)->getName() : null,
                $pendingAction->getExpiryDateTime()->format("Y-m-d H:i:s")
            );
        } catch (ItemNotFoundException $e) {
            throw new ValidationException(["invitationCode" => new FieldValidationError("invitationCode", "invalid", "Invalid invitation code supplied for account group invitation")]);
        }
    }


    /**
     * Accept an account group invitation.
     *
     * @param string $invitationCode
     *
     * @objectInterceptorDisabled
     */
    public function acceptAccountGroupInvitation(string $invitationCode) {

        try {
            $pendingAction = $this->pendingActionService->getPendingActionByIdentifier("ACCOUNT_GROUP_INVITE", $invitationCode);

            $accountId = $pendingAction->getData()["accountId"];
            $accountGroupId = $pendingAction->getObjectId();

            $pendingData = $pendingAction->getData();

            $accountGroupMember = new AccountGroupMember($accountGroupId, $pendingData["accountId"]);
            $accountGroupMember->save();

            // Remove the pending action once completed.
            $this->pendingActionService->removePendingAction("ACCOUNT_GROUP_INVITE", $invitationCode);

            $this->emailService->send(new AccountTemplatedEmail($accountId, "security/account-group-welcome", []), $accountId);

        } catch (ItemNotFoundException $e) {
            throw new ValidationException(["invitationCode" => new FieldValidationError("invitationCode", "invalid", "Invalid invitation code supplied for account group invitation")]);
        }


    }

}