<?php /** @noinspection ALL */

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
use Kiniauth\Services\Security\SecurityService;
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

/**
 * Account group service.
 *
 * @interceptor Kiniauth\Services\Security\AccountGroupInterceptor
 */
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
     * @var SecurityService
     */
    private SecurityService $securityService;


    /**
     * @param EmailService $emailService
     * @param PendingActionService $pendingActionService
     * @param ActiveRecordInterceptor $activeRecordInterceptor
     * @param AccountService $accountService
     * @param SecurityService $securityService
     */
    public function __construct(
        EmailService            $emailService,
        PendingActionService    $pendingActionService,
        ActiveRecordInterceptor $activeRecordInterceptor,
        AccountService          $accountService,
        SecurityService         $securityService
    ) {
        $this->emailService = $emailService;
        $this->pendingActionService = $pendingActionService;
        $this->activeRecordInterceptor = $activeRecordInterceptor;
        $this->accountService = $accountService;
        $this->securityService = $securityService;
    }
    
    private function getLoggedInAccountId(): ?int {

        $loggedInContext = $this->securityService->getLoggedInSecurableAndAccount();
        return $loggedInContext[1]->getAccountId() ?? null;
    }


    /**
     * Function to get a single account group based on supplied account group ID.
     * Will throw an exception if logged in account isn't in the account group.
     *
     * @param int $accountGroupId
     *
     * @return AccountGroup
     */
    public function getAccountGroup(int $accountGroupId): AccountGroup {
        return AccountGroup::fetch($accountGroupId);
    }


    /**
     * Function to get multiple account groups based on supplied account group IDs.
     * Will only return account groups the logged in account has access to.
     *
     * @param array $accountGroupIds
     *
     * @return AccountGroup[]
     */
    public function getMultipleAccountGroups(array $accountGroupIds): array {
        return AccountGroup::multiFetch($accountGroupIds);
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
     *
     * @return AccountGroupMember[]
     */
    public function getMembersOfAccountGroup(int $accountGroupId): array {

        $accountGroup = $this->getAccountGroup($accountGroupId);
        return $accountGroup->getAccountGroupMembers();
    }


    /**
     * Function to allow the account group owner to add a new account member to their account group.
     *
     * @param int $accountGroupId
     * @param int $newAccountId
     *
     * @return void
     * @throws InvalidAccountGroupOwnerException
     */
    public function addMemberToAccountGroup(int $accountGroupId, int $newAccountId): void {

        // get the logged in account id
        $loggedInAccountId = $this->getLoggedInAccountId();

        $accountGroup = $this->getAccountGroup($accountGroupId);

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
     *
     * @return void
     * @throws Exception
     */
    public function removeMemberFromAccountGroup(int $accountGroupId, int $removingAccountId): void {

        // get the logged in account id
        $loggedInAccountId = $this->getLoggedInAccountId();

        // Check the logged in account is the account group owner
        $accountGroup = $this->getAccountGroup($accountGroupId);

        // Check the logged in account is the account group owner
        if ($accountGroup->getOwnerAccountId() != $loggedInAccountId) {
            throw new InvalidAccountGroupOwnerException();
        }

        // Prevent the account group owner from removing themselves
        if ($accountGroup->getOwnerAccountId() == $removingAccountId) {
            throw new AccountGroupOwnerCannotRemoveThemselvesException();
        }

        try {
            /** @var AccountGroupMember $accountGroupMember */
            $accountGroupMember = AccountGroupMember::fetch([$accountGroupId, $removingAccountId]);
            $accountGroupMember->remove();
        } catch (ObjectNotFoundException) {
            return;
        }

    }


    /**
     * Function to remove a group account member from an account group.
     *
     * @param int $accountGroupId
     *
     * @return void
     * @throws AccountGroupOwnerCannotLeaveException
     */
    public function leaveAccountGroup(int $accountGroupId): void {

        // get the logged in account id
        $loggedInAccountId = $this->getLoggedInAccountId();

        $accountGroup = $this->getAccountGroup($accountGroupId);

        // Prevent the account group owner from leaving
        if ($accountGroup->getOwnerAccountId() == $loggedInAccountId) {
            throw new AccountGroupOwnerCannotLeaveException();
        }

        try {
            /** @var AccountGroupMember $accountGroupMember */
            $accountGroupMember = AccountGroupMember::fetch([$accountGroupId, $loggedInAccountId]);
            $accountGroupMember->remove();
        } catch (ObjectNotFoundException) {
            return;
        }

    }


    /**
     * Function to delete a given account group.
     * Only the owner of the account should be able to do so.
     *
     * @param int $accountGroupId
     *
     * @return void
     */
    public function deleteAccountGroup(int $accountGroupId): void {

        $accountGroup = $this->getAccountGroup($accountGroupId);

        $accountGroup->remove();
    }


    /**
     * Invite an account to join an account group.
     *
     * @param int $accountGroupId
     * @param string $accountExternalIdentifier
     * @return void
     *
     * @objectInterceptorDisabled
     * @throws InvalidAccountGroupOwnerException|AccountAlreadyAttachedToAccountGroupException|Throwable
     */
    public function inviteAccountToAccountGroup(int $accountGroupId, string $accountExternalIdentifier): void {

        // get the logged in account id
        $loggedInAccountId = $this->getLoggedInAccountId();

        // Get the account group
        $accountGroup = $this->getAccountGroup($accountGroupId);

        // Verify permission
        if ($accountGroup->getOwnerAccountId() != $loggedInAccountId) {
            throw new InvalidAccountGroupOwnerException();
        }

        // Get the account id for the external identifier
        $account = $this->accountService->getAccountByExternalIdentifier($accountExternalIdentifier);
        $accountId = $account->getAccountId();
        $accountName = $account->getName();

        // If already a member throw exception
        try {
            AccountGroupMember::fetch([$accountGroupId, $accountId]);
            $alreadyMember = true;
        } catch (ObjectNotFoundException) {
            $alreadyMember = false;
        }

        if ($alreadyMember) {
            throw new AccountAlreadyAttachedToAccountGroupException($accountName);
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
     *
     * @return AccountGroupInvitation[]
     * @throws AccessDeniedException|InvalidAccountGroupOwnerException
     */
    public function getActiveAccountGroupInvitationAccounts(int $accountGroupId): array {

        // get the logged in account id
        $loggedInAccountId = $this->getLoggedInAccountId();

        $accountGroup = $this->getAccountGroup($accountGroupId);

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
     *
     * @return void
     * @throws InvalidAccountGroupOwnerException
     */
    public function revokeAccountGroupInvitation(AccountGroupInvitation $invite): void {

        // get the logged in account id
        $loggedInAccountId = $this->getLoggedInAccountId();

        $accountGroupId = $invite->getAccountGroupId();
        $accountId = $invite->getAccountId();

        $accountGroup = $this->getAccountGroup($accountGroupId);

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
     *
     * @return AccountGroupInvitation
     * @throws AccessDeniedException|ValidationException
     * @objectInterceptorDisabled
     */
    public function getInvitationDetails(string $invitationCode): AccountGroupInvitation {

        try {
            $pendingAction = $this->pendingActionService->getPendingActionByIdentifier(
                "ACCOUNT_GROUP_INVITE",
                $invitationCode
            );
        } catch (ItemNotFoundException) {
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

        // get the logged in account id
        $loggedInAccountId = $this->getLoggedInAccountId();

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
     *
     * @throws AccessDeniedException
     * @throws Throwable|ValidationException
     * @objectInterceptorDisabled
     */
    public function acceptAccountGroupInvitation(string $invitationCode): void {

        try {
            $pendingAction = $this->pendingActionService->getPendingActionByIdentifier(
                "ACCOUNT_GROUP_INVITE",
                $invitationCode
            );
        }
        catch (ItemNotFoundException) {
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

        // get the logged in account id
        $loggedInAccountId = $this->getLoggedInAccountId();

        $accountId = $pendingAction->getData()["accountId"];
        $accountName = AccountLabel::fetch($accountId)?->getName();
        $accountGroupId = $pendingAction->getObjectId();

        // verify that the invitation belongs to the logged-in account
        if ($accountId != $loggedInAccountId) {
            throw new AccessDeniedException();
        }

        $accountGroupInterceptor = Container::instance()->get(AccountGroupInterceptor::class);
        $accountGroup =  $accountGroupInterceptor->executeInsecure(function () use ($accountGroupId) {
            return AccountGroup::fetch($accountGroupId);
        });

        // check to see that the account isn't already a member
        try {
            AccountGroupMember::fetch([$accountGroupId, $accountId]);
            $alreadyMember = true;
        }
        catch (ObjectNotFoundException) {
            $alreadyMember = false;
        }

        if ($alreadyMember) {
            // remove the pending invite anyway
            $this->pendingActionService->removePendingAction("ACCOUNT_GROUP_INVITE", $invitationCode);

            throw new AccountAlreadyAttachedToAccountGroupException($accountName);
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

        try {
            $this->emailService->send(
                new AccountTemplatedEmail($accountId, "security/account-group-welcome", []),
                $accountId
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }

}