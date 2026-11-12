<?php

namespace Kiniauth\Services\Account;

use Exception;
use Kiniauth\Exception\Security\AccountAlreadyAttachedToAccountGroupException;
use Kiniauth\Exception\Security\AccountGroupOwnerCannotLeaveException;
use Kiniauth\Exception\Security\AccountGroupOwnerCannotRemoveThemselvesException;
use Kiniauth\Exception\Security\InvalidAccountGroupOwnerException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountGroup;
use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Objects\Account\AccountLabel;
use Kiniauth\Objects\Communication\Email\AccountTemplatedEmail;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\AccountGroupInterceptor;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\ValueObjects\Account\AccountGroupDescriptor;
use Kiniauth\ValueObjects\Account\AccountGroupInvitation;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Throwable;

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
     * @param ActiveRecordInterceptor $activeRecordInterceptor
     * @param AccountService $accountService
     */
    public function __construct(
        EmailService            $emailService,
        PendingActionService    $pendingActionService,
        ActiveRecordInterceptor $activeRecordInterceptor,
        AccountService          $accountService
    ) {
        $this->emailService = $emailService;
        $this->pendingActionService = $pendingActionService;
        $this->activeRecordInterceptor = $activeRecordInterceptor;
        $this->accountService = $accountService;
    }


    /**
     * Function to get a single account group based on supplied account group ID.
     * Will throw an exception if logged in account isn't in the account group.
     *
     * @param int $accountGroupId
     * @param int $loggedInAccountId
     *
     * @return AccountGroup
     * @throws AccessDeniedException
     */
    public function getAccountGroup(
        int $accountGroupId,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): AccountGroup {

        /** @var AccountGroup $accountGroup */
        $accountGroup = AccountGroup::fetch($accountGroupId);

        // get the member Ids from the accountGroupMembers
        $accountGroupMemberAccountIds = array_map(function (AccountGroupMember $accountGroupMember) {
            return $accountGroupMember->getMemberAccountId();
        }, $accountGroup->getAccountGroupMembers());

        // only allow accounts that are members of this account group to view
        if (in_array($loggedInAccountId, $accountGroupMemberAccountIds)) {
            return $accountGroup;
        } else {
            throw new AccessDeniedException();
        }
    }


    /**
     * Function to get multiple account groups based on supplied account group IDs.
     * Will only return account groups the logged in account has access to.
     *
     * @param array $accountGroupIds
     * @param int   $loggedInAccountId
     *
     * @return AccountGroup[]
     */
    public function getMultipleAccountGroups(
        array $accountGroupIds,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): array {

        $accountGroups = AccountGroup::multiFetch($accountGroupIds);

        // return account groups the logged in account has access to
        return array_filter($accountGroups, function (AccountGroup $accountGroup) use ($loggedInAccountId) {

            $accountGroupMemberAccountIds = array_map(
                fn(AccountGroupMember $member) => $member->getMemberAccountId(),
                $accountGroup->getAccountGroupMembers()
            );

            return in_array($loggedInAccountId, $accountGroupMemberAccountIds);
        });
    }


    /**
     * Function to return all saved account groups.
     *
     * @return AccountGroup[]
     */
    public function getAllAccountGroups(): array {
        return AccountGroup::filter();
    }


    /**
     * Function to list all account groups for a given account ID.
     *
     * @param int $accountId
     *
     * @return AccountGroup[]
     */
    public function listAccountGroupsForAccount($accountId = Account::LOGGED_IN_ACCOUNT): array {

        /** @var AccountGroupMember[] $accountGroupMembers */
        $accountGroupMembers = AccountGroupMember::filter("WHERE member_account_id = ?", $accountId);

        $accountGroupIds = array_map(
            fn($accountGroupMember) => $accountGroupMember->getAccountGroupId(),
            $accountGroupMembers
        );

        return AccountGroup::multiFetch($accountGroupIds);
    }


    /**
     * Function to create a new account group.
     *
     * @param AccountGroupDescriptor $accountGroupDescriptor
     *
     * @return int
     */
    public function createAccountGroup(AccountGroupDescriptor $accountGroupDescriptor): int {

        $accountId = $accountGroupDescriptor->getOwnerAccountId() ?? Account::LOGGED_IN_ACCOUNT;

        $accountGroup = new AccountGroup(
            $accountGroupDescriptor->getName(),
            $accountGroupDescriptor->getDescription(),
            $accountGroupDescriptor->getType(),
            $accountId,
            [
                new AccountGroupMember(null, $accountId)
            ]
        );
        $accountGroup->save();
        return $accountGroup->getId();
    }


    /**
     * Function to get all the members of a given account group.
     * Will throw an exception if the logged in account is not a member of the given account group.
     *
     * @param int $accountGroupId
     * @param int $loggedInAccountId
     *
     * @return AccountGroupMember[]
     * @throws AccessDeniedException
     */
    public function getMembersOfAccountGroup(
        int $accountGroupId,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): array {

        $accountGroup = $this->getAccountGroup($accountGroupId, $loggedInAccountId);

        return $accountGroup->getAccountGroupMembers();
    }


    /**
     * Function to allow the account group owner to add a new account member to their account group.
     *
     * @param int $accountGroupId
     * @param int $newAccountId
     * @param int $loggedInAccountId
     *
     * @return void
     * @throws AccessDeniedException|InvalidAccountGroupOwnerException
     */
    public function addMemberToAccountGroup(
        int $accountGroupId,
        int $newAccountId,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): void {

        $accountGroup = $this->getAccountGroup($accountGroupId, $loggedInAccountId);

        if ($accountGroup->getOwnerAccountId() != $loggedInAccountId) {
            throw new InvalidAccountGroupOwnerException();
        }

        // Check if member exists
        try {
            AccountGroupMember::fetch([$accountGroupId, $newAccountId]);
            return;
        } catch (ObjectNotFoundException) {
            // Great - doesn't already exist
        }

        $accountGroup->addMember($newAccountId);
        $accountGroup->save();
    }


    /**
     * Function to allow the account group owner to remove an account member from their account group.
     *
     * @param int $accountGroupId
     * @param int $removingAccountId
     * @param int $loggedInAccountId
     *
     * @return void
     * @throws AccessDeniedException|Exception
     */
    public function removeMemberFromAccountGroup(
        int $accountGroupId,
        int $removingAccountId,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): void {

        // Check the logged in account is the account group owner
        $accountGroup = $this->getAccountGroup($accountGroupId, $loggedInAccountId);

        if ($accountGroup->getOwnerAccountId() != $loggedInAccountId) {
            throw new InvalidAccountGroupOwnerException();
        }

        if ($accountGroup->getOwnerAccountId() == $removingAccountId) {
            throw new AccountGroupOwnerCannotRemoveThemselvesException();
        }

        try {
            /** @var AccountGroupMember $accountGroupMember */
            $accountGroupMember = AccountGroupMember::fetch([$accountGroupId, $removingAccountId]);
            $accountGroupMember->remove();
        } catch (ObjectNotFoundException $e) {
            return;
        }

    }


    /**
     * Function to remove a group account member from an account group.
     *
     * @param int $accountGroupId
     * @param int $loggedInAccountId
     *
     * @return void
     * @throws AccessDeniedException|Exception
     */
    public function leaveAccountGroup(
        int $accountGroupId,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): void {

        $accountGroup = $this->getAccountGroup($accountGroupId, $loggedInAccountId);

        if ($accountGroup->getOwnerAccountId() == $loggedInAccountId) {
            throw new AccountGroupOwnerCannotLeaveException();
        }

        try {
            /** @var AccountGroupMember $accountGroupMember */
            $accountGroupMember = AccountGroupMember::fetch([$accountGroupId, $loggedInAccountId]);
            $accountGroupMember->remove();
        } catch (ObjectNotFoundException $e) {
            return;
        }

    }


    /**
     * Function to delete a given account group.
     * Only the owner of the account should be able to do so.
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

        $accountGroup = $this->getAccountGroup($accountGroupId, $loggedInAccountId);

        if ($accountGroup->getOwnerAccountId() != $loggedInAccountId) {
            throw new InvalidAccountGroupOwnerException();
        }

        $accountGroup->remove();
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
     * @throws InvalidAccountGroupOwnerException|AccountAlreadyAttachedToAccountGroupException|Throwable
     */
    public function inviteAccountToAccountGroup(
        int $accountGroupId,
        string $accountExternalIdentifier,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): void {

        // Get the account group
        $accountGroup = $this->getAccountGroup($accountGroupId, $loggedInAccountId);

        // Verify permission
        if ($accountGroup->getOwnerAccountId() != $loggedInAccountId) {
            throw new InvalidAccountGroupOwnerException();
        }

        // Get the account id for the external identifier
        $accountId = $this->accountService->getAccountByExternalIdentifier($accountExternalIdentifier)->getAccountId();

        // If already a member throw exception
        try {
            AccountGroupMember::fetch([$accountGroupId, $accountId]);
            $alreadyMember = true;
        } catch (ObjectNotFoundException) {
            $alreadyMember = false;
        }

        if ($alreadyMember) {
            throw new AccountAlreadyAttachedToAccountGroupException($accountId);
        }

        // Create a pending action for the invite
        $invitationCode = $this->pendingActionService->createPendingAction(
            "ACCOUNT_GROUP_INVITE",
            $accountGroupId,
            [
                "accountId" => $accountId
            ]
        );

        // if the email fails to be sent, roll back invitation
        try {
            // Allow insecure sending of email.
            $this->activeRecordInterceptor->executeInsecure(
                function () use ($accountId, $accountGroup, $invitationCode) {

                    // Send an invitation email attached to the account
                    $invitationEmail = new AccountTemplatedEmail(
                        $accountId,
                        "security/account-group-invite",
                        [
                            "accountGroup" => $accountGroup,
                            "invitationCode" => $invitationCode
                        ]
                    );

                    $this->emailService->send($invitationEmail, $accountId);
                }
            );
        }
        catch (Throwable $e) {
            $this->pendingActionService->removePendingAction(
                "ACCOUNT_GROUP_INVITE",
                $invitationCode
            );

            throw $e;
        }
    }


    /**
     * Function to get all the active account group invitations.
     * Only the owner should be able to see active group invitations for their group.
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

        $accountGroup = $this->getAccountGroup($accountGroupId, $loggedInAccountId);

        if ($accountGroup->getOwnerAccountId() != $loggedInAccountId) {
            throw new InvalidAccountGroupOwnerException();
        }

        $pendingActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId(
            "ACCOUNT_GROUP_INVITE",
            $accountGroupId
        );

        return array_map(function ($pendingAction) use ($accountGroup) {

            $accountId = $pendingAction->getData()["accountId"] ?? null;

            return new AccountGroupInvitation(
                $accountGroup->getId(),
                $accountGroup->getName(),
                $accountId,
                $accountId ? AccountLabel::fetch($accountId)?->getName() : null,
                $accountGroup->getOwnerAccountId() ? AccountLabel::fetch(
                    $accountGroup->getOwnerAccountId()
                )?->getName() : null,
                $pendingAction->getExpiryDateTime()->format("Y-m-d H:i:s")
            );
        }, $pendingActions);
    }


    /**
     * Function to revoke an invitation to an account group.
     * Only the owner of the group may revoke invitations.
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

        $accountGroupId = $invite->getAccountGroupId();
        $accountId = $invite->getAccountId();

        $accountGroup = $this->getAccountGroup($accountGroupId, $loggedInAccountId);

        if ($accountGroup->getOwnerAccountId() != $loggedInAccountId) {
            throw new InvalidAccountGroupOwnerException();
        }

        $pendingInvites = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId(
            "ACCOUNT_GROUP_INVITE",
            $accountGroupId
        ) ?? [];

        foreach ($pendingInvites as $invite) {
            if ($invite->getData()["accountId"] == $accountId) {
                $this->pendingActionService->removePendingAction(
                    "ACCOUNT_GROUP_INVITE",
                    $invite->getIdentifier()
                );
            }
        }

    }


    /**
     * Function to get email address associated with an invitation code, or report an issue.
     *
     * @param string    $invitationCode
     * @param int       $loggedInAccountId
     *
     * @return AccountGroupInvitation
     * @throws AccessDeniedException|ValidationException
     * @objectInterceptorDisabled
     */
    public function getInvitationDetails(
        string $invitationCode,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): AccountGroupInvitation {

        try {
            $pendingAction = $this->pendingActionService->getPendingActionByIdentifier(
                "ACCOUNT_GROUP_INVITE",
                $invitationCode
            );
        } catch (ItemNotFoundException $e) {
            throw new ValidationException(
                [
                    "invitationCode" => new FieldValidationError(
                        "invitationCode",
                        "invalid",
                        "Invalid invitation code supplied for account group invitation"
                    )
                ]
            );
        }

        $accountId = $pendingAction->getData()["accountId"] ?? null;

        // verify that the invitation belongs to the logged-in account
        if ($accountId != $loggedInAccountId) {
            throw new AccessDeniedException();
        }

        $accountGroup = AccountGroup::fetch($pendingAction->getObjectId());

        $requestingAccountId = $accountGroup->getOwnerAccountId();

        return new AccountGroupInvitation(
            $pendingAction->getObjectId(),
            $accountGroup->getName(),
            $accountId,
            $accountId ? AccountLabel::fetch($accountId)?->getName() : null,
            $requestingAccountId ? AccountLabel::fetch($requestingAccountId)->getName() : null,
            $pendingAction->getExpiryDateTime()->format("Y-m-d H:i:s")
        );
    }


    /**
     * Function to allow an account to accept a group invitation.
     *
     * @param string    $invitationCode
     * @param int       $loggedInAccountId
     *
     * @throws AccessDeniedException
     * @throws Throwable|ValidationException
     * @objectInterceptorDisabled
     */
    public function acceptAccountGroupInvitation(
        string $invitationCode,
        $loggedInAccountId = Account::LOGGED_IN_ACCOUNT
    ): void {

        try {
            $pendingAction = $this->pendingActionService->getPendingActionByIdentifier(
                "ACCOUNT_GROUP_INVITE",
                $invitationCode
            );
        }
        catch (ItemNotFoundException $e) {
            throw new ValidationException(
                [
                    "invitationCode" => new FieldValidationError(
                        "invitationCode",
                        "invalid",
                        "Invalid invitation code supplied for account group invitation"
                    )
                ]
            );
        }

        $accountId = $pendingAction->getData()["accountId"];
        $accountGroupId = $pendingAction->getObjectId();

        // verify that the invitation belongs to the logged-in account
        if ($accountId != $loggedInAccountId) {
            throw new AccessDeniedException();
        }

        $accountGroup = AccountGroup::fetch($accountGroupId);

        // check to see that the account isn't already a member
        try {
            AccountGroupMember::fetch([$accountGroupId, $accountId]);
            $alreadyMember = true;
        }
        catch (ObjectNotFoundException $e) {
            $alreadyMember = false;
        }

        if ($alreadyMember) {
            // remove the pending invite anyway
            $this->pendingActionService->removePendingAction("ACCOUNT_GROUP_INVITE", $invitationCode);

            throw new AccountAlreadyAttachedToAccountGroupException($accountId);
        }

        // Add account group member to list
        $accountGroupMembers = $accountGroup->getAccountGroupMembers();
        $accountGroupMembers[] = new AccountGroupMember($accountGroupId, $accountId);
        $accountGroup->setAccountGroupMembers($accountGroupMembers);

        // Save group to ensure consistency
        $accountGroupInterceptor = Container::instance()->get(AccountGroupInterceptor::class);
        $accountGroupInterceptor->executeInsecure(function () use ($accountGroup) {
            $accountGroup->save();
        });

        // Remove the pending action once completed.
        $this->pendingActionService->removePendingAction("ACCOUNT_GROUP_INVITE", $invitationCode);

        $this->emailService->send(
            new AccountTemplatedEmail($accountId, "security/account-group-welcome", []),
            $accountId
        );
    }

}