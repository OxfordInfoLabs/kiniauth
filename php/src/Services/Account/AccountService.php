<?php


namespace Kiniauth\Services\Account;


use Kiniauth\Exception\Security\UserAlreadyAttachedToAccountException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Communication\Email\AccountTemplatedEmail;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Application\ActivityLogger;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\RoleService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\ValueObjects\Security\AssignedRole;
use Kiniauth\ValueObjects\Security\ScopeObjectRolesAssignment;
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
     * @var UserService
     */
    private $userService;

    /**
     * Construct with required deps.
     *
     * @param SecurityService $securityService
     * @param PendingActionService $pendingActionService
     * @param EmailService $emailService
     * @param RoleService $roleService
     * @param UserService $userService
     */
    public function __construct($securityService, $pendingActionService, $emailService, $roleService, $userService) {
        $this->securityService = $securityService;
        $this->pendingActionService = $pendingActionService;
        $this->emailService = $emailService;
        $this->roleService = $roleService;
        $this->userService = $userService;
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
     * Search for accounts, optionally limiting by string and paging.
     *
     * @param string $searchString
     * @param int $offset
     * @param int $limit
     */
    public function searchForAccounts($searchString = "", $offset = 0, $limit = 10) {

        $whereClauses = [];
        $params = [];
        if ($searchString) {
            $whereClauses[] = "name LIKE ?";
            $params[] = "%$searchString%";
        }

        $query = (sizeof($whereClauses) ? "WHERE " : "") . join(" AND ", $whereClauses) . " ORDER BY name";

        if ($limit) {
            $query .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset) {
            $query .= " OFFSET ?";
            $params[] = $offset;
        }

        return AccountSummary::filter($query, $params);

    }


    /**
     * Create a new active account.  If admin email address and password are supplied an initial admin user is created
     * and assigned to the account.
     *
     * @param $accountName
     * @param $adminUserName
     * @param $adminUserEmailAddress
     * @param $adminUserPassword
     * @param integer $parentAccountId
     */
    public function createAccount($accountName, $adminEmailAddress = null, $adminHashedPassword = null, $adminName = null, $parentAccountId = null) {

        // Create an account to match with any name we can find.
        $account = Container::instance()->new(Account::class, false);
        $account->setName($accountName);
        $account->setParentAccountId($parentAccountId);
        $account->setStatus(Account::STATUS_ACTIVE);
        $account->save();

        if ($adminEmailAddress) {
            $this->userService->createUser($adminEmailAddress, $adminHashedPassword, $adminName, [
                new UserRole(Role::SCOPE_ACCOUNT, $account->getAccountId(), 0, $account->getAccountId())
            ]);
        }


        return $account->getAccountId();
    }


    /**
     * @param $newName
     * @param $password
     * @return bool
     */
    public function changeAccountName($newName, $password) {
        list($user, $account) = $this->securityService->getLoggedInUserAndAccount();

        $accountObject = Account::fetch($account->getAccountId());

        if ($this->securityService->validateUserPassword($user->getEmailAddress(), $password)) {

            $oldName = $accountObject->getName();

            $accountObject->setName($newName);
            $accountObject->save();

            ActivityLogger::log("Account name changed", null, null, [
                "From" => $oldName,
                "To" => $newName
            ], null, $accountObject->getAccountId());

            $this->securityService->reloadLoggedInObjects();
            return true;
        }
        return false;
    }


    /**
     * Invite a user to join an account.  In order to do this the user must be a super user for the account.
     * An array of user roles must be supplied as
     *
     * @param integer $accountId
     * @param string $emailAddress
     * @param ScopeObjectRolesAssignment[] $initialAssignedRoles
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
     * Get email address associated with an invitation code, or report an issue.
     *
     * @param $invitationCode
     * @objectInterceptorDisabled
     */
    public function getInvitationDetails($invitationCode) {

        try {
            $pendingAction = $this->pendingActionService->getPendingActionByIdentifier("USER_INVITE", $invitationCode);

            $account = $this->getAccountSummary($pendingAction->getObjectId());

            return [
                "emailAddress" => $pendingAction->getData()["emailAddress"],
                "newUser" => $pendingAction->getData()["newUser"],
                "accountName" => $account->getName()
            ];
        } catch (ItemNotFoundException $e) {
            throw new ValidationException(["invitationCode" => new FieldValidationError("invitationCode", "invalid", "Invalid invitation code supplied for user invitation")]);
        }
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
                $user->setStatus(User::STATUS_ACTIVE);
                $user->save();
            } else {
                // Get existing user if exists
                $user = User::filter("WHERE emailAddress = ? AND parentAccountId = ?", $pendingData["emailAddress"], $accountSummary->getParentAccountId())[0];
            }

            $objectBinder = Container::instance()->get(ObjectBinder::class);

            $this->roleService->updateAssignedScopeObjectRolesForUser($user->getId(), $objectBinder->bindFromArray($pendingData["initialRoles"], ScopeObjectRolesAssignment::class . "[]"), $pendingAction->getObjectId());

            // Remove the pending action once completed.
            $this->pendingActionService->removePendingAction("USER_INVITE", $invitationCode);

        } catch (ItemNotFoundException $e) {
            throw new ValidationException(["invitationCode" => new FieldValidationError("invitationCode", "invalid", "Invalid invitation code supplied for user invitation")]);
        }


    }


    /**
     * Remove a user from an account.  Requires logged in user to be account owner.
     *
     * @param $accountId
     * @param $userId
     *
     * @hasPrivilege ACCOUNT:*($accountId)
     */
    public function removeUserFromAccount($accountId, $userId) {

        $matchingUserRoles = UserRole::filter("WHERE accountId = ? AND userId=?", $accountId, $userId);
        foreach ($matchingUserRoles as $userRole) {
            $userRole->remove();
        }

    }


}
