<?php


namespace Kiniauth\Objects\Security;


use Kiniauth\Objects\Account\Account;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Base securable role class
 *
 * Class SecurableRole
 * @package Kiniauth\Objects\Security
 */
abstract class SecurableRole extends ActiveRecord {


    /**
     * The scope of this role (defaults to account).
     *
     * @var string
     * @primaryKey
     * @maxLength 50
     */
    protected $scope = Role::SCOPE_ACCOUNT;


    /**
     * The id of the scope object for which this role is being attached.  If set to blank this is assumed to
     * refer to all objects (i.e. superuser).
     *
     * @var string
     * @primaryKey
     *
     */
    protected $scopeId;


    /**
     * The role id for this user role.
     *
     * @var integer
     * @primaryKey
     */
    protected $roleId;


    /**
     * An optional account id which should be set if the role scope is ACCOUNT
     * or if the scope being limited represents sub objects within an account for
     * increased security.
     *
     * @var integer
     */
    protected $accountId;


    /**
     * The role object for this user role
     *
     * @manyToOne
     * @parentJoinColumns role_id
     * @readOnly
     *
     * @var Role
     */
    protected $role;

    /**
     * @manyToOne
     * @parentJoinColumns account_id
     * @readOnly
     *
     * @var Account
     */
    protected $account;

    /**
     * Construct a new user account role object.
     *
     * @param string $scope
     * @param string $scopeId
     * @param integer $roleId
     * @param integer $userId
     */
    public function __construct($scope = Role::SCOPE_ACCOUNT, $scopeId = null, $roleId = null, $accountId = null) {
        $this->scope = $scope;
        $this->scopeId = $scopeId;
        $this->roleId = $roleId;
        $this->accountId = $accountId;
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
    public function getScopeId() {
        return $this->scopeId;
    }

    /**
     * @return int
     */
    public function getRoleId() {
        return $this->roleId;
    }

    /**
     * @return Role
     */
    public function getRole() {
        return $this->role;
    }


    /**
     * @return string[]
     */
    public function getPrivileges() {
        return $this->role ? $this->role->getPrivileges() : [];
    }


    /**
     * Get account id if relevant
     */
    public function getAccountId() {
        return $this->accountId;
    }

    /**
     *
     * @return string
     */
    public function getAccountStatus() {
        return $this->account ? $this->account->getStatus() : null;
    }


    /**
     * Return array of account roles
     *
     * @return string[]
     */
    public function getAccountPrivileges() {
        return $this->account ? $this->account->returnAccountPrivileges() : [];
    }

    /**
     * For testing purposes
     *
     * @param Role $role
     */
    public function setRole($role) {
        $this->role = $role;
    }

}