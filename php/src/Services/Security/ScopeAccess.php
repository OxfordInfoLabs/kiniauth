<?php


namespace Kiniauth\Services\Security;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\SecurableRole;

/**
 * Scope access objects allow for configuration of both the Object and Method interceptors for a given scope.
 *
 * Class ScopeAccess
 */
abstract class ScopeAccess {


    /**
     * The scope of this access object
     *
     * @var string
     */
    private $scope;


    /**
     * The display description for this access object.
     *
     * @var string
     */
    private $scopeDescription;

    /**
     * The object member which will be checked for in object / method interceptors
     * for this scope access.
     *
     * @var string
     */
    private $objectMember;


    /**
     * Construct with a scope string
     *
     * @param $scope
     */
    public function __construct($scope, $scopeDescription, $objectMember = null) {
        $this->scope = $scope;
        $this->scopeDescription = $scopeDescription;
        $this->objectMember = $objectMember;
    }

    /**
     * @return string
     */
    public function getScope() {
        return $this->scope;
    }


    /**
     * @return string
     */
    public function getScopeDescription() {
        return $this->scopeDescription;
    }

    /**
     * @return string
     */
    public function getObjectMember() {
        return $this->objectMember;
    }


    /**
     * Generate scope privileges from either a user or an account (only one will be passed).
     * if an account is passed, it means it is an account based log in so will generally be granted full access to account items.
     *
     * This is used on log in to determine access to items of this scope type.  It should return an array of privilege keys indexed by the id of the
     * scope item.  The indexed array of account privileges is passed through for convenience.
     *
     * Use * as the scope key to indicate all accounts.
     *
     * @param Securable $securable
     * @param Account $account
     * @param string[] $accountPrivileges
     *
     * @return
     */
    public abstract function generateScopePrivileges($securable, $account, $accountPrivileges);


    /**
     * Return labels matching each scope id.  This enables the generic role assignment screen
     * to show sensible values.
     *
     * @param $scopeIds
     * @param null $accountId
     * @return mixed
     */
    public abstract function getScopeObjectDescriptionsById($scopeIds, $accountId = null);


    /**
     * Get filtered scope object descriptions with offset and limiting for paging purposes.  If supplied, the
     * account id should be used to filter these if required.
     *
     * @param string $searchFilter
     * @param integer $accountId
     */
    public abstract function getFilteredScopeObjectDescriptions($searchFilter, $offset = 0, $limit = 10, $accountId = null);


    /**
     * An optional method which may be overloaded to filter a set of securable roles based upon whether
     * the securable is permitted to have each role assigned.
     * This is particularly useful if e.g. the number of securables assigned a particular role is capped
     * or additional security checks should be made.
     *
     * Simply returns a boolean indicator which should be true if the assignment is able to proceed.
     *
     * Defaults to open assignment access.
     *
     * @param SecurableRole[] $userRoles
     * @return SecurableRole[]
     */
    public function getAssignableSecurableRoles($userRoles) {
        return $userRoles;
    }


}
