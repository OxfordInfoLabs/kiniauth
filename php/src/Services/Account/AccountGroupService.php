<?php

namespace Kiniauth\Services\Account;

use Kiniauth\Exception\Security\AccountAlreadyAttachedToAccountGroupException;
use Kiniauth\Exception\Security\InvalidAccountGroupOwnerException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountGroup;
use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Objects\Communication\Email\AccountTemplatedEmail;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\ValueObjects\Account\AccountGroupDescriptor;
use Kiniauth\ValueObjects\Account\AccountGroupInvitation;
use Kinikit\Core\Communication\Email\MissingEmailTemplateException;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

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
     * @param EmailService $emailService
     * @param PendingActionService $pendingActionService
     */
    public function __construct(EmailService $emailService, PendingActionService $pendingActionService) {
        $this->emailService = $emailService;
        $this->pendingActionService = $pendingActionService;
    }


    /**
     * @param int $accountGroupId
     * @return AccountGroup
     */
    public function getAccountGroup(int $accountGroupId): AccountGroup {
        return AccountGroup::fetch($accountGroupId);
    }

    /**
     * @return AccountGroup[]
     */
    public function getAllAccountGroups(): array {
        return AccountGroup::filter();
    }

    /**
     * @param AccountGroupDescriptor $accountGroupDescriptor
     * @return int
     */
    public function createAccountGroup(string $accountGroupDescriptor): int {
        $accountGroup = new AccountGroup(
            $accountGroupDescriptor->getName(),
            $accountGroupDescriptor->getDescription(),
            $accountGroupDescriptor->getOwnerAccountId() ?? Account::LOGGED_IN_ACCOUNT
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
        $accountGroupMember = new AccountGroupMember($accountGroupId, $accountId);
        $accountGroupMember->save();
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
     * @param int $accountId
     * @param int $loggedInAccountId
     * @return void
     */
    public function inviteAccountToAccountGroup(int $accountGroupId, int $accountId, $loggedInAccountId = Account::LOGGED_IN_ACCOUNT): void {

        // Get the account group
        $accountGroup = $this->getAccountGroup($accountGroupId);

        // Verify permission
        if ($accountGroup->getOwnerAccountId() !== $loggedInAccountId) {
            throw new InvalidAccountGroupOwnerException();
        }

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


        // Send an invitation email attached to the account
        $invitationEmail = new AccountTemplatedEmail($accountGroupId, "security/account-group-invite", [
            "accountGroup" => $accountGroup,
            "invitationCode" => $invitationCode
        ]);

        $this->emailService->send($invitationEmail, $accountId);

    }


    /**
     * @param int $accountGroupId
     * @return AccountGroupInvitation[]
     */
    public function getActiveAccountGroupInvitationAccounts(int $accountGroupId): array {
        $pendingActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("ACCOUNT_GROUP_INVITE", $accountGroupId);
        return array_map(function ($pendingAction) use ($accountGroupId) {
            return new AccountGroupInvitation(
                $accountGroupId,
                $pendingAction->getData()["account_id"] ?? null,
                $pendingAction->getExpiryDateTime()->format("Y-m-d H:i:s")
            );
        }, $pendingActions);
    }


    /**
     * Resend an account group invitation email
     *
     * @param AccountGroupInvitation $invite
     * @return void
     */
    public function resendAccountGroupInvitationEmail(AccountGroupInvitation $invite): void {

        $accountGroupId = $invite->getAccountGroupId();
        $accountId = $invite->getAccountId();

        // Get the active account invitation email addresses
        $pendingActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("ACCOUNT_GROUP_INVITE", $accountGroupId);

        foreach ($pendingActions as $pendingAction) {
            if ($pendingAction->getData()["account_id"] ?? null == $accountId) {

                $accountGroup = $this->getAccountGroup($pendingAction->getObjectId());

                // Send an invitation email attached to the account
                $invitationEmail = new AccountTemplatedEmail($accountId, "security/account-group-invite", [
                    "account_group" => $accountGroup,
                    "invitationCode" => $pendingAction->getIdentifier(),
                    "resent" => true,
                    "currentTime" => date("d/m/Y H:i:s")
                ]);

                $this->emailService->send($invitationEmail, $accountId);

                $pendingAction->resetExpiryTime();
                $pendingAction->save();

                break;
            }
        }
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

            return new AccountGroupInvitation(
                $pendingAction->getObjectId(),
                $pendingAction->getData()["account_id"],
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

            $accountId = $pendingAction->getData()["account_id"];
            $accountGroupId = $pendingAction->getObjectId();

            $pendingData = $pendingAction->getData();

            $accountGroupMember = new AccountGroupMember($accountGroupId, $pendingData["account_id"]);
            $accountGroupMember->save();

            // Remove the pending action once completed.
            $this->pendingActionService->removePendingAction("ACCOUNT_GROUP_INVITE", $invitationCode);

            $this->emailService->send(new AccountTemplatedEmail($accountId, "security/account-group-welcome", []), $accountId);

        } catch (ItemNotFoundException $e) {
            throw new ValidationException(["invitationCode" => new FieldValidationError("invitationCode", "invalid", "Invalid invitation code supplied for account group invitation")]);
        }


    }

}