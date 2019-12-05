<?php


namespace Kiniauth\Objects\Security;

use Kiniauth\Objects\Account\Account;
use Kinikit\Persistence\ORM\ActiveRecord;


/**
 * Encodes a role for a user on an account.
 *
 * @table ka_user_role
 * @generate
 */
class UserRole extends ActiveRecord {

    /**
     * The user id for which this account role is being attached
     *
     * @var integer
     * @primaryKey
     */
    protected $userId;


    /**
     * The scope of this role (defaults to account).
     *
     * @var string
     * @primaryKey
     */
    protected $scope = Role::SCOPE_ACCOUNT;


    /**
     * The id of the scope object for which this role is being attached.  If set to blank this is assumed to
     * refer to all objects (i.e. superuser).
     *
     * @var integer
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
     * @param integer $scopeId
     * @param integer $roleId
     * @param integer $userId
     */
    public function __construct($scope = Role::SCOPE_ACCOUNT, $scopeId = null, $roleId = null, $accountId = null, $userId = null) {
        $this->scope = $scope;
        $this->scopeId = $scopeId;
        $this->roleId = $roleId;
        $this->accountId = $accountId;
        $this->userId = $userId;
    }


    /**
     * @return int
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getScope() {
        return $this->scope;
    }

    /**
     * @return int
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


}
