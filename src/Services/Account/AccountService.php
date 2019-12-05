<?php


namespace Kiniauth\Services\Account;


use Kiniauth\Exception\Security\UserAlreadyAttachedToAccountException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Communication\Email\AccountTemplatedEmail;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\RoleService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\ValueObjects\Security\AssignedRole;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;

class AccountService {

    /**
     * @var SecurityService $securityService
     */
    private $securityService;

    /**
     * @var PendingActionService $pendingActionService
     */
    private $pendingActionService;


    /**
     * @var EmailService $emailService
     */
    private $emailService;


    /**
     * @var RoleService
     */
    private $roleService;

    /**
     * Construct with required deps.
     *
     * @param SecurityService $securityService
     * @param PendingActionService $pendingActionService
     * @param EmailService $emailService
     * @param RoleService $roleService
     */
    public function __construct($securityService, $pendingActionService, $emailService, $roleService) {
        $this->securityService = $securityService;
        $this->pendingActionService = $pendingActionService;
        $this->emailService = $emailService;
        $this->roleService = $roleService;
    }


    /**
     * Get an account summary, default to the logged in account.
     *
     * @param string $id
     * @return AccountSummary
     */
    public function getAccountSummary($id = Account::LOGGED_IN_ACCOUNT) {
        $accountSummary = AccountSummary::fetch($id);
        return $accountSummary;
    }


    /**
     * Invite a user to join an account.  In order to do this the user must be a super user for the account.
     * An array of user roles must be supplied as
     *
     * @param integer $accountId
     * @param string $emailAddress
     * @param AssignedRole[] $initialAssignedRoles
     *
     * @hasPrivilege ACCOUNT:*($accountId)
     * @objectInterceptorDisabled
     */
    public function inviteUserToAccount($accountId, $emailAddress, $initialAssignedRoles) {

        // Get the account summary
        $account = $this->getAccountSummary($accountId);

        // Get existing user if exists
        $existingUsers = User::filter("WHERE emailAddress = ? AND parentAccountId = ?", $emailAddress, $account->getParentAccountId());

        $newUser = true;

        // Handle new and old cases correctly
        if (sizeof($existingUsers) > 0) {
            $existingUserRoles = $existingUsers[0]->getRoles();
            foreach ($existingUserRoles as $userRole) {
                if ($userRole->getScope() == Role::SCOPE_ACCOUNT && $userRole->getScopeId() == $accountId)
                    throw new UserAlreadyAttachedToAccountException($emailAddress);
            }


            $newUser = false;
        }


        // Create a pending action for the invite
        $invitationCode = $this->pendingActionService->createPendingAction("USER_INVITE", $accountId, [
            "emailAddress" => $emailAddress,
            "initialRoles" => $initialAssignedRoles,
            "newUser" => $newUser
        ]);


        // Send an invitation email attached to the account
        $invitationEmail = new AccountTemplatedEmail($accountId, "security/invite-user", [
            "emailAddress" => $emailAddress,
            "invitationCode" => $invitationCode,
            "newUser" => $newUser
        ]);

        $this->emailService->send($invitationEmail, $accountId);

    }


    /**
     * Accept a user invitation.  If this is a brand new user at least a password must also be supplied and optionally
     * a name for the user.
     *
     * @param string $invitationCode
     * @param string $password
     * @param string $name
     *
     * @objectInterceptorDisabled
     */
    public function acceptUserInvitationForAccount($invitationCode, $password = null, $name = null) {

        try {
            $pendingAction = $this->pendingActionService->getPendingActionByIdentifier("USER_INVITE", $invitationCode);

            // Grab the account summary
            $accountSummary = $this->getAccountSummary($pendingAction->getObjectId());

            $pendingData = $pendingAction->getData();

            if ($pendingData["newUser"]) {
                $user = new User($pendingData["emailAddress"], $password, $name, $accountSummary->getParentAccountId());
                $user->save();
            } else {
                // Get existing user if exists
                $user = User::filter("WHERE emailAddress = ? AND parentAccountId = ?", $pendingData["emailAddress"], $accountSummary->getParentAccountId())[0];
            }

            $objectBinder = Container::instance()->get(ObjectBinder::class);

            $this->roleService->updateAssignedAccountRolesForUser($user->getId(), $objectBinder->bindFromArray($pendingData["initialRoles"], AssignedRole::class . "[]"), $pendingAction->getObjectId(), 1);

        } catch (ItemNotFoundException $e) {
            throw new ValidationException(["invitationCode" => new FieldValidationError("invitationCode", "invalid", "Invalid invitation code supplied for user invitation")]);
        }


    }

}
