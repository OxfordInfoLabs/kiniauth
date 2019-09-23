<?php


namespace Kiniauth\Services\Security;


use Kiniauth\Exception\Security\AccountSuspendedException;
use Kiniauth\Exception\Security\InvalidLoginException;
use Kiniauth\Exception\Security\MissingScopeObjectIdForPrivilegeException;
use Kiniauth\Exception\Security\NonExistentPrivilegeException;
use Kiniauth\Exception\Security\UserSuspendedException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Security\Privilege;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kinikit\Core\Object\SerialisableObject;
use Kinikit\Core\Util\SerialisableArrayUtils;
use Kinikit\MVC\Framework\SourceBaseManager;

class SecurityService {

    private $session;

    /**
     * @var ScopeAccess[]
     */
    private $scopeAccesses;


    /**
     * Indexed array of all privileges indexed by key.
     *
     * @var Privilege[string]
     */
    private $privileges;

    /**
     * @param \Kiniauth\Services\Application\Session $session
     * @param \Kiniauth\Services\Security\AccountScopeAccess $accountScopeAccess
     */
    public function __construct($session, $accountScopeAccess) {
        $this->session = $session;
        $this->scopeAccesses = [$accountScopeAccess];
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
    public function login($user = null, $account = null) {

        $this->logout();


        $accountId = null;

        if ($user) {


            // Throw suspended exception if user is suspended.
            if ($user->getStatus() == User::STATUS_SUSPENDED) {
                throw new UserSuspendedException();
            }

            // Throw invalid login if still pending.
            if ($user->getStatus() == User::STATUS_PENDING) {
                throw new InvalidLoginException();
            }


            $accountId = $user->getActiveAccountId();

            if (!$accountId && $user->getAccountIds()) {
                throw new AccountSuspendedException();
            }

            $this->session->__setLoggedInUser($user);


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
        foreach ($this->scopeAccesses as $scopeAccess) {
            $scopePrivileges = $scopeAccess->generateScopePrivileges($user, $account, $accountPrivileges);
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
        $this->session->__setLoggedInUser(null);
        $this->session->__setLoggedInAccount(null);
        $this->session->__setLoggedInPrivileges(null);
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
     * Get all privileges.  Maintain a cached copy of all privileges
     */
    public function getAllPrivileges() {

        if (!$this->privileges) {
            $this->privileges = array();


            foreach (SourceBaseManager::instance()->getSourceBases() as $sourceBase) {
                if (file_exists($sourceBase . "/Config/privileges.json")) {
                    $privText = file_get_contents($sourceBase . "/Config/privileges.json");
                    $privileges = SerialisableArrayUtils::convertArrayToSerialisableObjects(json_decode($privText, true), "\Kiniauth\Objects\Security\Privilege[]");
                    $this->privileges = array_merge($this->privileges, $privileges);
                }
            }

            $this->privileges = SerialisableArrayUtils::indexArrayOfObjectsByMember("key", $this->privileges);
        }


        return $this->privileges;
    }


    /**
     * Verify whether or not a logged in user can access an object by checking all available installed scope accesses.
     *
     * @param $object
     */
    public function checkLoggedInObjectAccess($object) {

        $accessors = $object->__findSerialisablePropertyAccessors();

        $access = true;
        foreach ($this->scopeAccesses as $scopeAccess) {
            $objectMember = $scopeAccess->getObjectMember();
            if ($objectMember && isset($accessors[strtolower($objectMember)])) {
                $scopeId = $object->__getSerialisablePropertyValue($objectMember);
                $access = $access && $this->getLoggedInScopePrivileges($scopeAccess->getScope(), $scopeId);
            }
            if (!$access)
                break;
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
    public function checkLoggedInHasPrivilege($privilegeKey, $scopeId = null) {

        $allPrivileges = $this->getAllPrivileges();

        // Throw straight away if a bad privilege key is passed.
        if (!isset($allPrivileges[$privilegeKey])) {
            throw new NonExistentPrivilegeException($privilegeKey);
        }

        // Return straight away if not logged in.
        $loggedInUser = $this->session->__getLoggedInUser();
        $loggedInAccount = $this->session->__getLoggedInAccount();
        if ($loggedInUser == null && $loggedInAccount == null) return false;

        $privilegeScope = $allPrivileges[$privilegeKey]->getScope();

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
            $privileges = $privileges + $allPrivileges[$scope][$scopeId];


        return $privileges;

    }


    /**
     * Check if the logged in user is a super user.
     */
    public function isSuperUserLoggedIn() {
        $allPrivileges = $this->session->__getLoggedInPrivileges();
        return isset($allPrivileges[Role::SCOPE_ACCOUNT]["*"]);
    }


}
