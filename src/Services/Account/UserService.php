<?php


namespace Kiniauth\Services\Account;

use Kiniauth\Exception\Security\InvalidUserAccessTokenException;
use Kiniauth\Exception\Security\TooManyUserAccessTokensException;
use Kiniauth\Exception\Security\TwoFactorAuthenticationRequiredException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Email\BrandedTemplatedEmail;
use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserAccessToken;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Objects\Security\UserSummary;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Security\TwoFactor\TwoFactorProvider;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\ValueObjects\Security\AssignedRole;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Communication\Email\TemplatedEmail;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Security\Hash\HashProvider;
use Kinikit\Core\Security\Hash\SHA512HashProvider;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Core\Util\StringUtils;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;


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
    private $emailService;

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
     * @objectInterceptorDisabled
     *
     * @return string
     */
    public function createPendingUserWithAccount($emailAddress, $password, $name = null, $accountName = null, $parentAccountId = null) {

        // Grab the parent account id if necessary.
        $parentAccountId = $parentAccountId === null ? $this->session->__getActiveParentAccountId() : $parentAccountId;

        // Create a new user, save it and return it back.
        $user = Container::instance()->new(User::class, false);
        $user->setEmailAddress($emailAddress);
        $user->setHashedPassword($password);
        $user->setName($name);
        $user->setParentAccountId($parentAccountId);

        // Check for duplication across parent accounts
        $matchingUsers = User::values("COUNT(*)", "WHERE emailAddress = ? AND parent_account_id = ?",
            $emailAddress,
            $parentAccountId);


        if ($matchingUsers[0] > 0) {
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
     * Create an admin user.
     *
     * @param $emailAddress
     * @param $password
     * @param null $name
     *
     */
    public function createAdminUser($emailAddress, $password, $name = null) {

        // Create a new user, save it and return it back.
        $user = new User($emailAddress, $password, $name);
        if ($validationErrors = $user->validate()) {
            throw new ValidationException($validationErrors);
        }

        $user->setRoles(array(new UserRole(Role::SCOPE_ACCOUNT, 0, 0)));
        $user->save();

        return $user;
    }


    /**
     * Activate an account with the supplied activation code.
     *
     * @param $activationCode
     *
     * @objectInterceptorDisabled
     */
    public function activateAccount($activationCode) {

        try {

            list($user, $account) = $this->getPendingUserAndAccount($activationCode);

            // Simply save the account
            $account->save();

            // Activate and save the user
            $user->setRoles(array(new UserRole(Role::SCOPE_ACCOUNT, $account->getAccountId(), 0, $account->getAccountId())));
            $user->setStatus(User::STATUS_ACTIVE);
            $user->save();

            $this->pendingActionService->removePendingAction("USER_ACTIVATION", $activationCode);

            return User::fetch($user->getId());

        } catch (ItemNotFoundException $e) {
            throw new ValidationException(["activationCode" => new FieldValidationError("activationCode", "invalid", "Invalid activation code supplied for user")]);
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

            $user = User::fetch($pendingAction->getObjectId());
            $user->setStatus(User::STATUS_ACTIVE);
            $user->setInvalidLoginAttempts(0);
            $user->save();

            $this->pendingActionService->removePendingAction("USER_LOCKED", $unlockCode);

        } catch (ItemNotFoundException $e) {
            throw new ValidationException(["unlockCode" => new FieldValidationError("unlockCode", "invalid", "Invalid unlock code supplied for user")]);

        }

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
            $identifier = $this->pendingActionService->createPendingAction("PASSWORD_RESET", $userId);

            // Send the email
            $this->emailService->send(new UserTemplatedEmail($userId, "security/password-reset", ["code" => $identifier]), null, $userId);
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

            $this->pendingActionService->removePendingAction("PASSWORD_RESET", $resetCode);

        } catch (ItemNotFoundException $e) {
            throw new ValidationException(["resetCode" => new FieldValidationError("resetCode", "invalid", "Invalid reset code supplied for password reset")]);
        }

    }


    /**
     * @param $newEmailAddress
     * @param $password
     * @param string $userId
     */
    public function changeUserEmail($newEmailAddress, $password, $userId = User::LOGGED_IN_USER) {
        /** @var User $user */
        $user = User::fetch($userId);
        if ($this->validateUserPassword($user->getEmailAddress(), $password)) {
            $user->setEmailAddress($newEmailAddress);
            $user->save();
            return $user;
        }
    }

    /**
     * @param $newMobile
     * @param $password
     * @param string $userId
     * @return User
     */
    public function changeUserMobile($newMobile, $password, $userId = User::LOGGED_IN_USER) {
        /** @var User $user */
        $user = User::fetch($userId);
        if ($this->validateUserPassword($user->getEmailAddress(), $password)) {
            $user->setMobileNumber($newMobile);
            $user->save();
            return $user;
        }
    }

    /**
     * @param $newEmailAddress
     * @param $password
     * @param string $userId
     * @return User
     */
    public function changeUserBackupEmail($newEmailAddress, $password, $userId = User::LOGGED_IN_USER) {
        /** @var User $user */
        $user = User::fetch($userId);
        if ($this->validateUserPassword($user->getEmailAddress(), $password)) {
            $user->setBackupEmailAddress($newEmailAddress);
            $user->save();
            return $user;
        }
    }

    public function changeUserDetails($newEmailAddress, $newName, $password, $userId) {
        /** @var User $user */
        $user = User::fetch($userId);
        if ($this->validateUserPassword($user->getEmailAddress(), $password)) {
            $user->setBackupEmailAddress($newEmailAddress);
            $user->setName($newName);
            $user->save();
            return $user;
        }
        return null;
    }


    public function generateTwoFactorSettings($userId = User::LOGGED_IN_USER) {

        /** @var User $user */
        $user = User::fetch($userId);

        $backupCodes = [];
        for ($i = 0; $i < 10; $i++) {
            $backupCodes[] = StringUtils::generateRandomString(9, false);
        }
        $user->setBackupCodes($backupCodes);
        $user->save();

        $this->twoFactorProvider->setAccountName($user->getEmailAddress());

        $secret = $this->twoFactorProvider->createSecretKey();
        $qrCode = $this->twoFactorProvider->generateQRCode($secret);

        return array("secret" => $secret, "qrCode" => $qrCode, "backupCodes" => $backupCodes);
    }

    public function authenticateNewTwoFactor($code, $secret, $userId = User::LOGGED_IN_USER) {

        /** @var User $user */
        $user = User::fetch($userId);

        $authenticated = $this->twoFactorProvider->authenticate($secret, $code);

        if ($authenticated) {
            $user->setTwoFactorData($secret);
            $user->save();
            return $user;
        }
        return false;
    }

    public function disableTwoFactor($userId = User::LOGGED_IN_USER) {

        /** @var User $user */
        $user = User::fetch($userId);

        $user->setTwoFactorData(null);
        $user->setBackupCodes(null);
        $user->save();
        return $user;
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

        $loggedIn = $this->session->__getLoggedInUser();

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
