<?php


namespace Kiniauth\Services\Security;


use Kiniauth\Attributes\Security\AccessNonActiveScopes;
use Kiniauth\Exception\Security\AccountSuspendedException;
use Kiniauth\Exception\Security\InvalidLoginException;
use Kiniauth\Exception\Security\MissingScopeObjectIdForPrivilegeException;
use Kiniauth\Exception\Security\NonExistentPrivilegeException;
use Kiniauth\Exception\Security\UserSuspendedException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Objects\Security\Privilege;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\Securable;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Objects\Security\UserSummary;
use Kiniauth\Services\Application\Session;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use Kinikit\Core\Util\ObjectArrayUtils;
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


    // Access mode constants for permission checking
    const ACCESS_READ = "READ";
    const ACCESS_WRITE = "WRITE";
    const ACCESS_GRANT = "GRANT";


    /**
     * @param Session $session
     * @param ScopeManager $scopeManager
     * @param ClassInspectorProvider $classInspectorProvider
     * @param FileResolver $fileResolver
     * @param ObjectBinder $objectBinder
     * @param UserSessionService $userSessionService
     */
    public function __construct($session, $scopeManager, $classInspectorProvider, $fileResolver, $objectBinder, $userSessionService) {
        $this->session = $session;
        $this->classInspectorProvider = $classInspectorProvider;
        $this->fileResolver = $fileResolver;
        $this->objectBinder = $objectBinder;
        $this->scopeManager = $scopeManager;
        $this->userSessionService = $userSessionService;
    }


    /**
     * Login as either a user or an account.  This should usually be called from
     * an Authentication service.  It sets up the session variables required to maintain state.
     *
     * @param Securable $securable
     * @param Account $account
     * @throws AccountSuspendedException
     * @throws InvalidLoginException
     * @throws UserSuspendedException
     */
    public function login($securable = null, $account = null, $userAccessTokenHash = null) {

        $this->logout();

        $accountId = null;

        if ($securable) {

            // Throw suspended exception if user is suspended.
            if ($securable->getStatus() == User::STATUS_SUSPENDED) {
                throw new UserSuspendedException();
            }

            // Throw invalid login if still pending.
            if ($securable->getStatus() == User::STATUS_PENDING || $securable->getStatus() == User::STATUS_LOCKED) {
                throw new InvalidLoginException();
            }

            $accountId = $securable->getActiveAccountId();

            if (!$accountId && $securable->getAccountIds()) {
                throw new AccountSuspendedException();
            }

            // Regenerate the session to avoid session fixation
            $this->session->regenerate();

            $this->session->__setLoggedInSecurable($securable);

            if ($userAccessTokenHash) {
                $this->session->__setLoggedInUserAccessTokenHash($userAccessTokenHash);
            } else if ($securable instanceof User) {

                // If regular interactive login, record this as a logged in session
                // And update successful logins.
                $this->userSessionService->registerNewAuthenticatedSession($securable->getId());

                // Update the user and re-store in session to prevent inconsistencies.
                $securable->setSuccessfulLogins($securable->getSuccessfulLogins() + 1);
                $this->session->__setLoggedInSecurable($securable);
                $securable->save();

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

        // Populate session privileges
        $this->populateSessionPrivileges($securable, $account);

    }


    /**
     * Become super user (Dangerous method, should be used with caution !!)
     */
    public function becomeSuperUser() {

        // Create adhoc user
        $user = new User("admin@admin", null, "Super User", 0, 0);
        $user->setStatus(User::STATUS_ACTIVE);
        $user->setRoles([new UserRole(Role::SCOPE_ACCOUNT)]);

        $this->session->__setLoggedInSecurable($user);
        $this->session->__setLoggedInPrivileges([Role::SCOPE_ACCOUNT => $this->scopeManager->getScopeAccess(Role::SCOPE_ACCOUNT)->generateScopePrivileges($user, null, [])]);

    }


    /**
     * Become a securable of the passed type and id (Dangerous method, should be used with caution !!)
     *
     * @param string $securableType
     * @param integer $securableId
     *
     * @objectInterceptorDisabled
     */
    public function becomeSecurable($securableType, $securableId) {

        if ($securableType == "USER") {
            $securable = User::fetch($securableId);
        } else if ($securableType == "API_KEY") {
            $securable = APIKey::fetch($securableId);
        }
        $this->session->__setLoggedInSecurable($securable);

        // If active account id, add to session
        $account = null;
        if ($securable->getActiveAccountId()) {
            $account = $this->becomeAccount($securable->getActiveAccountId());
        }

        $this->populateSessionPrivileges($securable, $account);

    }


    /**
     * Become and account (Dangerous method, should be used with caution)
     *
     * @param $accountId
     * @return void
     *
     * @objectInterceptorDisabled
     */
    public function becomeAccount($accountId) {

        $account = Account::fetch($accountId);
        $this->session->__setLoggedInAccount($account);

        $this->populateSessionPrivileges(null, $account);

        return $account;
    }


    // Populate session privileges
    private function populateSessionPrivileges($securable, $account) {

        /**
         * Process all scope accesses and build the global privileges array
         */
        $privileges = array();

        // Add account scope access
        $accountPrivileges = null;
        foreach ($this->scopeManager->getScopeAccesses() as $scopeAccess) {

            $scopePrivileges = $scopeAccess->generateScopePrivileges($securable, $account, $accountPrivileges);

            $privileges[$scopeAccess->getScope()] = $scopePrivileges;
            if ($scopeAccess->getScope() == Role::SCOPE_ACCOUNT) $accountPrivileges = $scopePrivileges;
        }

        $this->session->__setLoggedInPrivileges($privileges);

    }


    /**
     * Log out implementation.  Usually called from authentication service.
     */
    public function logout() {

        // Clean down the session to remove any previously logged in state
        $this->session->__setLoggedInSecurable(null);
        $this->session->__setLoggedInAccount(null);
        $this->session->__setLoggedInPrivileges(null);
        $this->session->__setLoggedInUserAccessTokenHash(null);
        $this->session->__clearCSRFToken();

        // Regenerate the session to avoid session fixation
        $this->session->regenerate();

    }


    /**
     * Return an array with both logged in user and account for convenience
     *
     * @return array
     */
    public function getLoggedInSecurableAndAccount() {
        return array($this->session->__getLoggedInSecurable(), $this->session->__getLoggedInAccount());
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
    public function checkLoggedInObjectAccess($object, $accessMode = self::ACCESS_READ) {


        // If super user, shortcut the process.
        if ($this->isSuperUserLoggedIn())
            return true;


        // Shortcut if we are the logged in user
        $loggedInSecurable = $this->session->__getLoggedInSecurable();
        $loggedInAccount = $this->session->__getLoggedInAccount();


        // Handle user as a special case
        if ($object instanceof UserSummary) {

            if ($loggedInSecurable) {
                if ($loggedInSecurable->getId() == $object->getId())
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

            $accessGroupGranted = [];
            foreach ($this->scopeManager->getScopeAccesses() as $scopeAccess) {

                $objectScopeAccesses = $this->scopeManager->generateObjectScopeAccesses($object, $scopeAccess->getScope());

                // Only continue if at least one object scope access detected
                if (sizeof($objectScopeAccesses)) {

                    // Now loop through the detected object scope accesses and calculate
                    foreach ($objectScopeAccesses as $objectScopeAccess) {
                        $scopeId = $objectScopeAccess->getRecipientPrimaryKey();
                        $accessGroup = $objectScopeAccess->getAccessGroup();

                        // Ensure access group granted defined
                        if (!isset($accessGroupGranted[$accessGroup])) {
                            $accessGroupGranted[$accessGroup] = true;
                        }

                        if ($scopeId === null || $scopeId == -1) {
                            if ($scopeAccess->getScope() == Role::SCOPE_ACCOUNT) {

                                // Special case for new accounts.
                                if (($object instanceof Account) && $loggedInAccount) {
                                    $accessGroupGranted[$accessGroup] = $accessGroupGranted[$accessGroup] && ($loggedInAccount->getSubAccountsEnabled() && ($loggedInAccount->getAccountId() == $object->getParentAccountId()));
                                } else {
                                    $accessGroupGranted[$accessGroup] = $accessGroupGranted[$accessGroup] && ($accessMode == self::ACCESS_READ && ($loggedInSecurable || $loggedInAccount));
                                }
                            }
                        } else {

                            // Calculate an initial check as to whether this object has been granted the right level of access
                            $accessModeMatches = ($accessMode == self::ACCESS_READ) || (($accessMode == self::ACCESS_WRITE) && $objectScopeAccess->getWriteAccess())
                                || (($accessMode == self::ACCESS_GRANT) && $objectScopeAccess->getGrantAccess());

                            // Compare with logged in privileges if we are accessing non active scopes
                            // or the scope id matches
                            if ($accessModeMatches && ($classInspector->hasClassAttribute(AccessNonActiveScopes::class)
                                    || (!$scopeAccess->getActiveScopeValue() ||
                                        ($scopeId == $scopeAccess->getActiveScopeValue())))) {
                                $accessGroupGranted[$accessGroup] = $accessGroupGranted[$accessGroup] && $this->getLoggedInScopePrivileges($scopeAccess->getScope(),
                                        $scopeId);
                            } // Otherwise assume false for this
                            else {
                                $accessGroupGranted[$accessGroup] = false;
                            }
                        }

                    }

                }


            }

        }

        // Return at least one true
        return sizeof($accessGroupGranted) == 0 || in_array(true, $accessGroupGranted);

    }


    /**
     * Lightweight check as to whether or not a passed object is accessible by a supplied scope object e.g. an account.
     * Used when we are whitelisting accounts for access
     *
     * @param mixed $object
     * @param string $scope
     * @param mixed $scopeId
     * @param string $accessMode
     *
     * @return boolean
     */
    public function checkObjectScopeAccess($object, $scope, $scopeId, $accessMode = self::ACCESS_READ) {

        // Grab scope accesses for object
        $objectScopeAccesses = ObjectArrayUtils::groupArrayOfObjectsByMember("recipientPrimaryKey", $this->scopeManager->generateObjectScopeAccesses($object, $scope));

        // If no object scope accesses defined always return true.
        if (sizeof($objectScopeAccesses) == 0) {
            return true;
        }

        // If at least one scope
        if (isset($objectScopeAccesses[$scopeId])) {
            foreach ($objectScopeAccesses[$scopeId] as $scopeAccess) {
                if (($accessMode == self::ACCESS_READ) || (($accessMode == self::ACCESS_WRITE) && $scopeAccess->getWriteAccess()) ||
                    (($accessMode == self::ACCESS_GRANT) && $scopeAccess->getGrantAccess())) {
                    return true;
                }
            }
        }
        return false;


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
        $loggedInSecurable = $this->session->__getLoggedInSecurable();
        $loggedInAccount = $this->session->__getLoggedInAccount();
        if ($loggedInSecurable == null && $loggedInAccount == null) return false;


        // Resolve missing ids.
        if (!$scopeId) {

            // Throw if no scope id supplied for a non account role.
            if ($privilegeScope != Role::SCOPE_ACCOUNT) {
                throw new MissingScopeObjectIdForPrivilegeException($privilegeKey);
            } else {
                // Fall back to logged in user / account
                if ($loggedInSecurable) $scopeId = $loggedInSecurable->getActiveAccountId();
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
        $loggedInUserAndAccount = $this->getLoggedInSecurableAndAccount();
        $databaseConnection = Container::instance()->get(DatabaseConnection::class);
        if ($accountId) {
            if (!isset($loggedInUserAndAccount[1]) || $loggedInUserAndAccount[1]->getAccountId() != $accountId) {
                return $databaseConnection->query("SELECT parent_account_id FROM ka_account WHERE account_id = ?", $accountId)->fetchAll()[0]["parent_account_id"] ?? 0;
            }
        }
        if ($userId) {
            if (!isset($loggedInUserAndAccount[0]) || $loggedInUserAndAccount[0]->getId() != $userId) {
                return $databaseConnection->query("SELECT parent_account_id FROM ka_user WHERE id = ?", $userId)->fetchAll()[0]["parent_account_id"] ?? 0;
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
     *
     * @objectInterceptorDisabled
     */
    public function reloadLoggedInObjects() {
        list($securable, $account) = $this->getLoggedInSecurableAndAccount();
        if ($securable) {
            $newSecurable = $securable instanceof User ? User::fetch($securable->getId()) : APIKey::fetch($securable->getId());
            $this->session->__setLoggedInSecurable($newSecurable);
            if ($newSecurable->getActiveAccountId())
                $this->session->__setLoggedInAccount(Account::fetch($newSecurable->getActiveAccountId()));

            $this->populateSessionPrivileges($securable, $account);
        } else if ($account) {
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
