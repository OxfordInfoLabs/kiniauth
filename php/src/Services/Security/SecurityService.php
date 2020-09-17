<?php


namespace Kiniauth\Services\Security;


use Kiniauth\Exception\Security\AccountSuspendedException;
use Kiniauth\Exception\Security\InvalidLoginException;
use Kiniauth\Exception\Security\MissingScopeObjectIdForPrivilegeException;
use Kiniauth\Exception\Security\NonExistentPrivilegeException;
use Kiniauth\Exception\Security\UserSuspendedException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\Privilege;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Application\Session;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use Kinikit\Core\Util\ObjectArrayUtils;

use Kinikit\Core\Util\StringUtils;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;

class SecurityService {

    private $session;


    /**
     * @var ClassInspectorProvider
     */
    private $classInspectorProvider;


    /**
     * Indexed array of all privileges indexed by key.
     *
     * @var Privilege[string]
     */
    private $privileges;


    /**
     * @var FileResolver
     */
    private $fileResolver;


    /**
     * @var ObjectBinder
     */
    private $objectBinder;


    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @var DatabaseConnection
     */
    private $databaseConnection;

    /**
     * @var UserSessionService
     */
    private $userSessionService;


    /**
     * @param Session $session
     * @param ScopeManager $scopeManager
     * @param ClassInspectorProvider $classInspectorProvider
     * @param FileResolver $fileResolver
     * @param ObjectBinder $objectBinder
     * @param DatabaseConnection $databaseConnection
     * @param UserSessionService $userSessionService
     */
    public function __construct($session, $scopeManager, $classInspectorProvider, $fileResolver, $objectBinder, $databaseConnection, $userSessionService) {
        $this->session = $session;
        $this->classInspectorProvider = $classInspectorProvider;
        $this->fileResolver = $fileResolver;
        $this->objectBinder = $objectBinder;
        $this->databaseConnection = $databaseConnection;
        $this->scopeManager = $scopeManager;
        $this->userSessionService = $userSessionService;
    }


    /**
     * Login as either a user or an account.  This should usually be called from
     * an Authentication service.  It sets up the session variables required to maintain state.
     *
     * @param User $user
     * @param Account $account
     * @throws AccountSuspendedException
     * @throws InvalidLoginException
     * @throws UserSuspendedException
     */
    public function login($user = null, $account = null, $userAccessTokenHash = null) {

        $this->logout();


        $accountId = null;

        if ($user) {

            // Throw suspended exception if user is suspended.
            if ($user->getStatus() == User::STATUS_SUSPENDED) {
                throw new UserSuspendedException();
            }

            // Throw invalid login if still pending.
            if ($user->getStatus() == User::STATUS_PENDING || $user->getStatus() == User::STATUS_LOCKED) {
                throw new InvalidLoginException();
            }


            $accountId = $user->getActiveAccountId();

            if (!$accountId && $user->getAccountIds()) {
                throw new AccountSuspendedException();
            }

            // Regenerate the session to avoid session fixation
            $this->session->regenerate();

            $this->session->__setLoggedInUser($user);

            if ($userAccessTokenHash) {
                $this->session->__setLoggedInUserAccessTokenHash($userAccessTokenHash);
            } else {
                // If regular interactive login, record this as a logged in session
                // And update successful logins.
                $this->userSessionService->registerNewAuthenticatedSession($user->getId());

                // Update the user and re-store in session to prevent inconsistencies.
                $user->setSuccessfulLogins($user->getSuccessfulLogins() + 1);
                $this->session->__setLoggedInUser($user);
                $user->save();


            }


        }

        if ($account) {

            if ($account->getStatus() == Account::STATUS_SUSPENDED) {
                throw new AccountSuspendedException();
            }

            $accountId = $account->getAccountId();
        }


        // If an accountId, read it and store it.
        if ($accountId) {
            $account = Account::fetch($accountId);
            $this->session->__setLoggedInAccount($account);
        }

        /**
         * Process all scope accesses and build the global privileges array
         */
        $privileges = array();


        // Add account scope access
        $accountPrivileges = null;
        foreach ($this->scopeManager->getScopeAccesses() as $scopeAccess) {

            $scopePrivileges = $scopeAccess->generateScopePrivileges($user, $account, $accountPrivileges);
            $privileges[$scopeAccess->getScope()] = $scopePrivileges;
            if ($scopeAccess->getScope() == Role::SCOPE_ACCOUNT) $accountPrivileges = $scopePrivileges;
        }

        $this->session->__setLoggedInPrivileges($privileges);

        $this->session->__setCSRFToken(StringUtils::generateRandomString(32, true, true));

    }


    /**
     * Log out implementation.  Usually called from authentication service.
     */
    public function logout() {

        // Clean down the session to remove any previously logged in state
        $this->session->__setLoggedInUser(null);
        $this->session->__setLoggedInAccount(null);
        $this->session->__setLoggedInPrivileges(null);
        $this->session->__setLoggedInUserAccessTokenHash(null);
        $this->session->__setCSRFToken(null);

        // Regenerate the session to avoid session fixation
        $this->session->regenerate();

    }


    /**
     * Return an array with both logged in user and account for convenience
     *
     * @return array
     */
    public function getLoggedInUserAndAccount() {
        return array($this->session->__getLoggedInUser(), $this->session->__getLoggedInAccount());
    }


    /**
     * Get the current CSRF token
     *
     * @return mixed
     */
    public function getCSRFToken() {
        return $this->session->__getCSRFToken();
    }


    /**
     * Get all privileges.  Maintain a cached copy of all privileges
     */
    public function getAllPrivileges() {

        if (!$this->privileges) {
            $this->privileges = array();


            foreach ($this->fileResolver->getSearchPaths() as $sourceBase) {
                if (file_exists($sourceBase . "/Config/privileges.json")) {
                    $privText = file_get_contents($sourceBase . "/Config/privileges.json");


                    $privileges = $this->objectBinder->bindFromArray(json_decode($privText, true), "\Kiniauth\Objects\Security\Privilege[]");

                    $this->privileges = array_merge($this->privileges, $privileges);
                }
            }

            $this->privileges = ObjectArrayUtils::indexArrayOfObjectsByMember(["scope", "key"], $this->privileges);
        }


        return $this->privileges;
    }


    /**
     * Verify whether or not a logged in user can access an object by checking all available installed scope accesses.
     *
     * @param $object
     */
    public function checkLoggedInObjectAccess($object) {

        // If super user, shortcut the process.
        if ($this->isSuperUserLoggedIn())
            return true;

        // Handle user as a special case
        if ($object instanceof User) {

            // Shortcut if we are the logged in user
            $loggedInUser = $this->session->__getLoggedInUser();

            if ($loggedInUser) {
                if ($loggedInUser->getId() == $object->getId())
                    return true;

                // Otherwise check to see whether we have any roles for this account
                foreach ($object->getRoles() as $role) {
                    if ($role->getAccountId())
                        if ($privs = $this->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, $role->getAccountId())) {
                            return in_array("*", $privs);
                        }

                }
            }

            return false;

        } else {

            $classInspector = $this->classInspectorProvider->getClassInspector(get_class($object));

            $access = true;
            foreach ($this->scopeManager->getScopeAccesses() as $scopeAccess) {
                $objectMember = $scopeAccess->getObjectMember();
                if ($objectMember && $classInspector->hasAccessor($objectMember)) {
                    $scopeId = $classInspector->getPropertyData($object, $objectMember);
                    $access = $access && $this->getLoggedInScopePrivileges($scopeAccess->getScope(), $scopeId);
                }
                if (!$access)
                    break;
            }

        }

        return $access;

    }


    /**
     * Check whether or not the logged in entity has the privilege for the passed
     * scope.  If the scope id is supplied as null we either complain unless the scope
     * is ACCOUNT in which case we fall back to the logged in account id as a convention.
     *
     * @param $privilegeKey
     * @param $scopeId
     */
    public function checkLoggedInHasPrivilege($privilegeScope, $privilegeKey, $scopeId = null) {

        $allPrivileges = $this->getAllPrivileges();


        // Throw straight away if a bad privilege key is passed.
        if (!isset($allPrivileges[$privilegeScope][$privilegeKey]) && $privilegeKey != "*") {
            throw new NonExistentPrivilegeException($privilegeScope, $privilegeKey);
        }

        // Return straight away if not logged in.
        $loggedInUser = $this->session->__getLoggedInUser();
        $loggedInAccount = $this->session->__getLoggedInAccount();
        if ($loggedInUser == null && $loggedInAccount == null) return false;


        // Resolve missing ids.
        if (!$scopeId) {

            // Throw if no scope id supplied for a non account role.
            if ($privilegeScope != Role::SCOPE_ACCOUNT) {
                throw new MissingScopeObjectIdForPrivilegeException($privilegeKey);
            } else {
                // Fall back to logged in user / account
                if ($loggedInUser) $scopeId = $loggedInUser->getActiveAccountId();
                else if ($loggedInAccount) $scopeId = $loggedInAccount->getAccountId();
            }
        }


        // Now do the main check
        $loggedInPrivileges = $this->getLoggedInScopePrivileges($privilegeScope, $scopeId);

        return in_array($privilegeKey, $loggedInPrivileges) || in_array("*", $loggedInPrivileges);

    }


    /**
     * Get all privileges for a given scope and scope id.
     *
     * @param integer $accountId
     *
     * @return string[]
     */
    public function getLoggedInScopePrivileges($scope, $scopeId) {

        $allPrivileges = $this->session->__getLoggedInPrivileges();

        // Merge any global privileges in.
        $privileges = array();
        if (isset($allPrivileges[$scope]["*"])) {
            $privileges = $allPrivileges[$scope]["*"];
        }

        if (isset($allPrivileges[$scope][$scopeId]))
            $privileges = array_merge($privileges, $allPrivileges[$scope][$scopeId]);

        return $privileges;

    }


    /**
     * Return the logged in user active parent account id.
     *
     * @return int
     */
    public function getParentAccountId($accountId = null, $userId = null) {
        $loggedInUserAndAccount = $this->getLoggedInUserAndAccount();
        if ($accountId) {
            if (!isset($loggedInUserAndAccount[1]) || $loggedInUserAndAccount[1]->getAccountId() != $accountId) {
                return $this->databaseConnection->query("SELECT parent_account_id FROM ka_account WHERE account_id = ?", $accountId)->fetchAll()[0]["parent_account_id"] ?? 0;
            }
        }
        if ($userId) {
            if (!isset($loggedInUserAndAccount[0]) || $loggedInUserAndAccount[0]->getId() != $userId) {
                return $this->databaseConnection->query("SELECT parent_account_id FROM ka_user WHERE id = ?", $userId)->fetchAll()[0]["parent_account_id"] ?? 0;
            }
        }
        return $this->session->__getActiveParentAccountId();
    }


    /**
     * Check if the logged in user is a super user.
     */
    public function isSuperUserLoggedIn() {
        $allPrivileges = $this->session->__getLoggedInPrivileges();
        return isset($allPrivileges[Role::SCOPE_ACCOUNT]["*"]);
    }


    /**
     * Reload logged in user and account.  Useful after any live changes have been made to accounts etc.
     */
    public function reloadLoggedInObjects() {
        list($user, $account) = $this->getLoggedInUserAndAccount();
        if ($user) {
            $this->session->__setLoggedInUser(User::fetch($user->getId()));
        }
        if ($account) {
            $this->session->__setLoggedInAccount(Account::fetch($account->getAccountId()));
        }
    }


    public function validateUserPassword($emailAddress, $password, $parentAccountId = null) {
        if ($parentAccountId === null) {
            $parentAccountId = $this->session->__getActiveParentAccountId() ? $this->session->__getActiveParentAccountId() : 0;
        }

        $matchingUsers = User::filter("WHERE emailAddress = ? AND parentAccountId = ?", $emailAddress, $parentAccountId);

        return sizeof($matchingUsers) > 0 && $matchingUsers[0]->passwordMatches($password, $this->session->__getSessionSalt());
    }

}
