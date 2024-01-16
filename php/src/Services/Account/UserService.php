<?php


namespace Kiniauth\Services\Account;

use Kiniauth\Exception\Security\InvalidAccountForUserException;
use Kiniauth\Exception\Security\InvalidUserAccessTokenException;
use Kiniauth\Exception\Security\TooManyUserAccessTokensException;
use Kiniauth\Exception\Security\TwoFactorAuthenticationRequiredException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Communication\Email\AccountTemplatedEmail;
use Kiniauth\Objects\Communication\Email\BrandedTemplatedEmail;
use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserAccessToken;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Objects\Security\UserSummary;
use Kiniauth\Services\Application\ActivityLogger;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Security\TwoFactor\TwoFactorProvider;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\ValueObjects\Security\AssignedRole;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Security\Hash\HashProvider;
use Kinikit\Core\Security\Hash\SHA512HashProvider;
use Kinikit\Core\Util\StringUtils;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;


class UserService {


    /**
     * @var TwoFactorProvider
     */
    private $twoFactorProvider;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var PendingActionService
     */
    protected $pendingActionService;


    /**
     * @var EmailService
     */
    protected $emailService;

    /**
     * @var HashProvider
     */
    private $hashProvider;

    /**
     * @var ObjectBinder
     */
    protected $objectBinder;


    /**
     * UserService constructor.
     *
     * @param TwoFactorProvider $twoFactorProvider
     * @param Session $session
     * @param PendingActionService $pendingActionService
     * @param EmailService $emailService
     * @param HashProvider $hashProvider
     * @param ObjectBinder $objectBinder
     */
    public function __construct($twoFactorProvider, $session, $pendingActionService, $emailService, $hashProvider, $objectBinder) {
        $this->twoFactorProvider = $twoFactorProvider;
        $this->session = $session;
        $this->pendingActionService = $pendingActionService;
        $this->emailService = $emailService;
        $this->hashProvider = $hashProvider;
        $this->objectBinder = $objectBinder;
    }


    /**
     * Create a brand new user - optionally supply a name, account name and parent account id if relevant.  If no
     * parent Account Id is supplied, the session context will be used.
     *
     * The action identifier is returned as a string
     *
     * @param array $customData
     * @objectInterceptorDisabled
     *
     * @return string
     */
    public function createPendingUserWithAccount($emailAddress, $password, $name = null, $accountName = null, $customData = [], $parentAccountId = null) {

        // Grab the parent account id if necessary.
        $parentAccountId = $parentAccountId === null ? $this->session->__getActiveParentAccountId() : $parentAccountId;

        // Create a new user, save it and return it back.
        $user = Container::instance()->new(User::class, false);
        $user->setEmailAddress($emailAddress);
        $user->setHashedPassword($password);
        $user->setName($name);
        $user->setParentAccountId($parentAccountId);
        $user->setCustomData($customData);

        $validationErrors = $user->validate();

        if (isset($validationErrors["emailAddress"])) {
            $this->emailService->send(new BrandedTemplatedEmail("security/duplicate-account", ["name" => $name], null, null, [$user->getFullEmailAddress()]));
        } else {

            // Create an account to match with any name we can find.
            $account = Container::instance()->new(Account::class, false);
            $account->setName($accountName ? $accountName : ($name ? $name : $emailAddress));
            $account->setParentAccountId($parentAccountId);

            // Create a pending activation action
            $actionIdentifier = $this->pendingActionService->createPendingAction("USER_ACTIVATION", "NEW", [
                "user" => $user,
                "account" => $account
            ]);

            $this->emailService->send(new BrandedTemplatedEmail("security/activate-account", ["code" => $actionIdentifier, "name" => $name], null, null, [$user->getFullEmailAddress()]));

            return $actionIdentifier;
        }


    }


    /**
     * Create new system user - name and roles are optional.  If no password is supplied, send an email
     * with a randomly assigned password.
     *
     *
     *
     * @param $emailAddress
     * @param $password
     * @param string $name
     * @param UserRole[] $roles
     */
    public function createUser($emailAddress, $hashedPassword = null, $name = null, $roles = []) {


        // Create a new user, save it and return it back.
        $user = Container::instance()->new(User::class, false);
        $user->setEmailAddress($emailAddress);
        $user->setHashedPassword($hashedPassword);
        $user->setName($name);
        $user->setStatus(User::STATUS_ACTIVE);


        if (!$hashedPassword) {
            $plainPassword = $user->generateAndUpdatePassword();
        }

        $user->setRoles($roles);
        $user->save();

        if (!$hashedPassword) {
            $this->emailService->send(new BrandedTemplatedEmail("security/user-welcome", ["emailAddress" => $emailAddress, "password" => $plainPassword], null, $user->getId()));
        }

        return $user->getId();
    }


    /**
     * Create an admin user.
     *
     * @param $emailAddress
     * @param $hashedPassword
     * @param null $name
     *
     */
    public function createAdminUser($emailAddress, $hashedPassword, $name = null) {
        return $this->createUser($emailAddress, $hashedPassword, $name, array(new UserRole(Role::SCOPE_ACCOUNT, 0, 0)));
    }


    /**
     * Activate an account with the supplied activation code.
     *
     * @param $activationCode
     *
     * @objectInterceptorDisabled
     */
    public function activateAccount($activationCode, $sendEmail = true) {

        try {

            list($user, $account) = $this->getPendingUserAndAccount($activationCode);

            // Simply save the account
            $account->setStatus(Account::STATUS_ACTIVE);
            $account->save();

            // Activate and save the user
            $user->setRoles(array(new UserRole(Role::SCOPE_ACCOUNT, $account->getAccountId(), 0, $account->getAccountId()),
                new UserRole(Role::SCOPE_PROJECT, "*", 0, $account->getAccountId())));
            $user->setStatus(User::STATUS_ACTIVE);
            $user->save();

            $this->pendingActionService->removePendingAction("USER_ACTIVATION", $activationCode);

            if ($sendEmail)
                $this->emailService->send(new AccountTemplatedEmail($account->getAccountId(), "account/account-welcome"));

            return User::fetch($user->getId());

        } catch (ItemNotFoundException $e) {
            throw new ValidationException(["activationCode" => new FieldValidationError("activationCode", "invalid", "Invalid activation code supplied for user")]);
        }

    }


    /**
     * Get accounts for the user passed by id
     *
     * @param $userId
     */
    public function getUserAccounts($userId = User::LOGGED_IN_USER) {
        return AccountSummary::filter("WHERE accountId IN (SELECT r.account_id FROM ka_user_role r WHERE r.user_id = ?) ORDER BY name", $userId);
    }


    /**
     * Switch active account for a user
     *
     * @param $accountId
     * @param string $userId
     */
    public function switchActiveAccount($accountId, $userId = User::LOGGED_IN_USER) {

        // Get the user
        $user = User::fetch($userId);

        $accounts = AccountSummary::filter("WHERE accountId IN 
            (SELECT r.account_id FROM ka_user_role r WHERE r.user_id = ? AND r.account_id = ?) ORDER BY name", $userId, $accountId);

        if (sizeof($accounts) > 0) {

            // Update active account.
            $user->setActiveAccountId($accountId);
            $user->save();

            Container::instance()->get(SecurityService::class)->reloadLoggedInObjects();
        } else {
            throw new InvalidAccountForUserException();
        }

    }


    /**
     * Lock a user by id
     *
     * @param $userId
     */
    public function lockUser($userId) {

        $user = User::fetch($userId);
        $user->setStatus(User::STATUS_LOCKED);

        // Create a pending action for the unlock operation
        $unlockCode = $this->pendingActionService->createPendingAction("USER_LOCKED", $user->getId());

        // Send an unlock email
        $email = new UserTemplatedEmail($user->getId(), "security/user-locked", [
            "unlockCode" => $unlockCode]);

        $this->emailService->send($email);

        // Save the user
        $user->save();

        ActivityLogger::log("User Locked", null, null, [], $userId);

        return $unlockCode;

    }


    /**
     * Unlock a user using an unlock code
     *
     * @param $unlockCode
     *
     * @objectInterceptorDisabled
     */
    public function unlockUser($unlockCode) {

        try {
            $pendingAction = $this->pendingActionService->getPendingActionByIdentifier("USER_LOCKED", $unlockCode);

            $this->unlockUserByUserId($pendingAction->getObjectId());

            $this->pendingActionService->removePendingAction("USER_LOCKED", $unlockCode);

        } catch (ItemNotFoundException $e) {
            throw new ValidationException(["unlockCode" => new FieldValidationError("unlockCode", "invalid", "Invalid unlock code supplied for user")]);

        }

    }

    /**
     * Used by Admin to unlock an account
     *
     * @param $userId
     */
    public function unlockUserByUserId($userId) {
        $user = User::fetch($userId);
        $user->setStatus(User::STATUS_ACTIVE);
        $user->setInvalidLoginAttempts(0);
        $user->save();

        ActivityLogger::log("User Unlocked", null, null, [], $user->getId());

    }

    /**
     * Suspend a user
     *
     * @param $userId
     */
    public function suspendUser($userId) {
        $user = User::fetch($userId);
        $user->setStatus(User::STATUS_SUSPENDED);
        $user->save();

        ActivityLogger::log("User Suspended", null, null, [], $user->getId());

    }


    /**
     * Get a user by id
     *
     * @param $id
     * @return User
     */
    public function getUser($id) {
        return User::fetch($id);
    }

    /**
     * Get all users matching a specific role scope and scope id, optionally limited to roles
     *
     * @param string $roleScope
     * @param $roleScopeId
     * @param $roleId
     *
     * @return User[]
     */
    public function getUsersWithRole($roleScope, $roleScopeId, $roleId = null) {

        if ($roleId) {
            return User::filter("WHERE roles.scope = ? AND roles.scope_id = ? AND roles.role_id = ? ORDER BY id", $roleScope, $roleScopeId, $roleId);
        } else {
            return User::filter("WHERE roles.scope = ? AND roles.scope_id = ? ORDER BY id", $roleScope, $roleScopeId);
        }

    }


    /**
     * Search for account users - limit and offset as supplied - optionally restricted to an account.
     *
     * @param string $searchString
     * @param int $offset
     * @param int $limit
     * @param int $accountId
     */
    public function searchForUsers($searchString, $offset = 0, $limit = 10, $accountId = null) {

        // Sort out params
        $searchString = "%" . $searchString . "%";
        $filterValues = [$searchString, $searchString];
        $offset = $offset ? $offset : 0;

        $query = "WHERE (name LIKE ? OR emailAddress LIKE ?)";
        if ($accountId) {
            $query .= " AND roles.account_id = ?";
            $filterValues[] = $accountId;
        } else if ($accountId === 0) {
            $query .= " AND roles.account_id IS NULL";
        }

        $totalRecords = UserSummary::values("COUNT(DISTINCT(id))", $query, $filterValues);

        // Now run full query.
        $fullQuery = $query . " ORDER BY IFNULL(name, 'ZZZZZZ') LIMIT ? OFFSET ?";
        $filterValues[] = $limit;
        $filterValues[] = $offset;
        $rawResults = UserSummary::filter($fullQuery, $filterValues);


        return [
            "results" => $rawResults,
            "totalRecords" => $totalRecords[0]
        ];


    }


    /**
     * Issue a password reset for a user with the supplied email address.
     *
     * @param string $emailAddress
     * @param integer $userId
     *
     * @objectInterceptorDisabled
     */
    public function sendPasswordReset($emailAddress = null) {

        $parentAccountId = $this->session->__getActiveParentAccountId() ? $this->session->__getActiveParentAccountId() : 0;

        // Check for a matching user
        $matchingUsers = User::filter("WHERE emailAddress = ? AND parentAccountId = ?", $emailAddress, $parentAccountId);


        // If a matching user, proceed otherwise do nothing
        if (sizeof($matchingUsers) > 0) {

            // Create a pending action
            $userId = $matchingUsers[0]->getId();
            $identifier = $this->pendingActionService->createPendingAction("PASSWORD_RESET", $userId, $emailAddress);

            // Send the email
            $this->emailService->send(new UserTemplatedEmail($userId, "security/password-reset", ["code" => $identifier]), null, $userId);

            ActivityLogger::log("Password Reset Requested", null, null, [], $userId);

        }


    }


    /**
     * Return the email address for verification code or null if none matches
     *
     * @param $resetCode
     *
     * @objectInterceptorDisabled
     */
    public function getEmailForPasswordResetCode($resetCode) {
        try {
            $pendingAction = $this->pendingActionService->getPendingActionByIdentifier("PASSWORD_RESET", $resetCode);
            return $pendingAction->getData();
        } catch (ItemNotFoundException $e) {
            throw new ValidationException(["resetCode" => new FieldValidationError("resetCode", "invalid", "Invalid reset code supplied for password reset")]);

        }
    }


    /**
     * Change password using a reset code.
     *
     * @param string $resetCode
     * @param string $newPassword
     *
     * @objectInterceptorDisabled
     */
    public function changePassword($resetCode, $newPassword) {

        try {
            $pendingAction = $this->pendingActionService->getPendingActionByIdentifier("PASSWORD_RESET", $resetCode);

            /**
             * @var User $user
             */
            $user = User::fetch($pendingAction->getObjectId());
            $user->setHashedPassword($newPassword);
            $user->save();


            ActivityLogger::log("Password changed", null, null, [], $user->getId());


            $this->pendingActionService->removePendingAction("PASSWORD_RESET", $resetCode);

        } catch (ItemNotFoundException $e) {
            throw new ValidationException(["resetCode" => new FieldValidationError("resetCode", "invalid", "Invalid reset code supplied for password reset")]);
        }

    }


    /**
     * @param $newEmailAddress
     * @param $password
     * @param null $hashedPassword
     * @param string $userId
     */
    public function changeUserEmail($newEmailAddress, $password, $hashedPassword = null, $userId = User::LOGGED_IN_USER) {
        /** @var User $user */
        $user = User::fetch($userId);
        if ($this->validateUserPassword($user->getEmailAddress(), $password)) {

            $from = $user->getEmailAddress();

            $user->setEmailAddress($newEmailAddress);
            if ($hashedPassword) {
                $user->setHashedPassword($hashedPassword);
            }
            $user->save();

            ActivityLogger::log("Email address changed", null, null, [
                "From" => $from,
                "To" => $newEmailAddress
            ], $userId);

            return true;
        } else {
            throw new ValidationException(["password" => [
                "invalid" => new FieldValidationError("password", "invalid", "The supplied password was incorrect")
            ]]);
        };
    }

    /**
     * @param $newName
     * @param $password
     * @param string $userId
     * @return bool
     */
    public function changeUserName($newName, $password, $userId = User::LOGGED_IN_USER) {
        /** @var User $user */
        $user = User::fetch($userId);
        if ($this->validateUserPassword($user->getEmailAddress(), $password)) {

            $from = $user->getName();

            $user->setName($newName);
            $user->save();

            ActivityLogger::log("User name changed", null, null, [
                "From" => $from,
                "To" => $newName
            ], $userId);

            return true;
        } else {
            throw new ValidationException(["password" => [
                "invalid" => new FieldValidationError("password", "invalid", "The supplied password was incorrect")
            ]]);
        };
    }


    /**
     * Direct change of password as initiated from the dashboard.  Existing password is supplied in hashed format for
     * comparison and validation
     *
     * @param $newHashedPassword
     * @param $existingPassword
     */
    public function changeUserPassword($newHashedPassword, $existingPassword, $userId = User::LOGGED_IN_USER) {

        /** @var User $user */
        $user = User::fetch($userId);

        // If existing password valid, save new password
        if ($this->validateUserPassword($user->getEmailAddress(), $existingPassword)) {
            $user->setHashedPassword($newHashedPassword);
            $user->save();
        } else {
            throw new ValidationException(["password" => [
                "invalid" => new FieldValidationError("password", "invalid", "The supplied password was incorrect")
            ]]);
        }
    }


    /**
     * @param $newMobile
     * @param $password
     * @param string $userId
     * @return bool
     */
    public function changeUserMobile($newMobile, $password, $userId = User::LOGGED_IN_USER) {
        /** @var User $user */
        $user = User::fetch($userId);
        if ($this->validateUserPassword($user->getEmailAddress(), $password)) {

            $from = $user->getMobileNumber();

            $user->setMobileNumber($newMobile);
            $user->save();

            ActivityLogger::log("User mobile number changed", null, null, [
                "From" => $from,
                "To" => $newMobile
            ], $userId);

            return true;
        } else {
            throw new ValidationException(["password" => [
                "invalid" => new FieldValidationError("password", "invalid", "The supplied password was incorrect")
            ]]);
        };
    }

    /**
     * @param $newEmailAddress
     * @param $password
     * @param string $userId
     * @return bool
     */
    public function changeUserBackupEmail($newEmailAddress, $password, $userId = User::LOGGED_IN_USER) {
        /** @var User $user */
        $user = User::fetch($userId);
        if ($this->validateUserPassword($user->getEmailAddress(), $password)) {

            $from = $user->getBackupEmailAddress();

            $user->setBackupEmailAddress($newEmailAddress);
            $user->save();

            ActivityLogger::log("User backup email changed", null, null, [
                "From" => $from,
                "To" => $newEmailAddress
            ], $userId);


            return true;
        } else {
            throw new ValidationException(["password" => [
                "invalid" => new FieldValidationError("password", "invalid", "The supplied password was incorrect")
            ]]);
        };
    }

    public function changeUserDetails($newEmailAddress, $newName, $password, $userId) {
        /** @var User $user */
        $user = User::fetch($userId);
        if ($this->validateUserPassword($user->getEmailAddress(), $password)) {
            $user->setBackupEmailAddress($newEmailAddress);
            $user->setName($newName);
            $user->save();
            return true;
        } else {
            throw new ValidationException(["password" => [
                "invalid" => new FieldValidationError("password", "invalid", "The supplied password was incorrect")
            ]]);
        };
    }


    /**
     * Create a user access token for a user supplying email address, password and 2fa if required.
     *
     * @param string $emailAddress
     * @param string $password
     * @param string $twoFaCode
     */
    public function createUserAccessToken($emailAddress, $password, $twoFaCode = null) {

        $authenticationService = Container::instance()->get(AuthenticationService::class);

        // Attempt login
        $status = $authenticationService->login($emailAddress, $password);

        // If 2fa, check twofa code as well
        if ($status == AuthenticationService::STATUS_REQUIRES_2FA) {
            if (!$authenticationService->authenticateTwoFactor($twoFaCode))
                throw new TwoFactorAuthenticationRequiredException();
        }

        $loggedIn = $this->session->__getLoggedInSecurable();

        // Check maximum number of tokens not reached
        $maxTokens = Configuration::readParameter("max.useraccess.tokens") ?? 5;
        $currentTotal = UserAccessToken::values("COUNT(*)", "WHERE userId = ?", $loggedIn->getId())[0];

        if ($currentTotal >= $maxTokens)
            throw new TooManyUserAccessTokensException();

        // Generate a random token
        $token = StringUtils::generateRandomString(32);

        // Create the user access token
        $userAccessToken = new UserAccessToken($loggedIn->getId(), $token);
        $userAccessToken->save();

        ActivityLogger::log("User access token generated", null, null, []);


        return $token;

    }


    /**
     * Allows an additional security factor to user access tokens which may be used
     * at the discretion of the application developer to provide more locked down access.
     *
     * @param $existingUserAccessToken
     * @param $secondaryToken
     */
    public function addSecondaryTokenToUserAccessToken($existingUserAccessToken, $secondaryToken) {

        $hashProvider = new SHA512HashProvider();
        $existingEntry = UserAccessToken::filter("WHERE tokenHash = ?", $hashProvider->generateHash($existingUserAccessToken));

        if (sizeof($existingEntry) > 0) {

            // Update token hash with secondary entry.
            $token = $existingEntry[0];
            $token->setTokenHash($hashProvider->generateHash($existingUserAccessToken . "--" . $secondaryToken));
            $token->save();

        } else {
            throw new InvalidUserAccessTokenException();
        }


    }


    /**
     * Update user settings for the supplied user id
     *
     * @param array $settings
     * @param integer $userId
     */
    public function updateUserApplicationSettings($settings = [], $userId = User::LOGGED_IN_USER) {

        /** @var User $user */
        $user = User::fetch($userId);

        // Grab existing settings and merge accordingly
        $existingSettings = $user->getApplicationSettings();
        $newSettings = array_merge($existingSettings, $settings);

        $user->setApplicationSettings($newSettings);
        $user->save();


    }


    /**
     * Get pending user and account from
     */
    protected function getPendingUserAndAccount($pendingActionIdentifier) {

        $pendingAction = $this->pendingActionService->getPendingActionByIdentifier("USER_ACTIVATION", $pendingActionIdentifier);


        // Map account and user
        $account = $this->objectBinder->bindFromArray($pendingAction->getData()["account"], Container::instance()->getClassMapping(Account::class), false);
        $user = $this->objectBinder->bindFromArray($pendingAction->getData()["user"], Container::instance()->getClassMapping(User::class), false);

        return [$user, $account];

    }


    /**
     * Update the pending user and account
     *
     * @param $pendingActionIdentifier
     * @param $user
     * @param $account
     */
    protected function updatePendingUserAndAccount($pendingActionIdentifier, $newUser, $newAccount) {

        $pendingAction = $this->pendingActionService->getPendingActionByIdentifier("USER_ACTIVATION", $pendingActionIdentifier);
        $pendingAction->setData(["user" => $newUser, "account" => $newAccount]);
        $pendingAction->save();

    }


    private function validateUserPassword($emailAddress, $password, $parentAccountId = null) {
        if ($parentAccountId === null) {
            $parentAccountId = $this->session->__getActiveParentAccountId() ? $this->session->__getActiveParentAccountId() : 0;
        }

        $matchingUsers = User::filter("WHERE emailAddress = ? AND parentAccountId = ?", $emailAddress, $parentAccountId);

        return sizeof($matchingUsers) > 0 && $matchingUsers[0]->passwordMatches($password, $this->session->__getSessionSalt());
    }


}
