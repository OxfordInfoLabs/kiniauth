<?php


namespace Kiniauth\Objects\Security;


use Kinikit\Persistence\UPF\Object\ActiveRecord;


/**
 * Encodes a role for a user on an account.
 *
 * @ormTable kc_user_role
 * @ormView kc_vw_user_role
 *
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
     * The role id to be attached to a user and account - if left blank this is assumed to mean all roles.
     *
     * @var integer
     * @primaryKey
     */
    protected $roleId;


    /**
     * The read only array of privileges from the role object
     *
     * @var string[]
     * @formatter json
     * @readOnly
     */
    protected $privileges;


    /**
     * Read only status for the linked account (only applicable in the case that the scope is ACCOUNT).
     *
     * @var string
     * @readOnly
     */
    protected $accountStatus;


    /**
     * Construct a new user account role object.
     *
     * @param integer $accountId
     * @param integer $roleId
     */
    public function __construct($scope = Role::SCOPE_ACCOUNT, $scopeId = null, $roleId = null, $userId = null) {
        $this->scope = $scope;
        $this->scopeId = $scopeId;
        $this->roleId = $roleId;
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
     * @return string[]
     */
    public function getPrivileges() {
        return $this->privileges;
    }


    /**
     * Get account id if relevant
     */
    public function getAccountId() {
        return $this->scope == Role::SCOPE_ACCOUNT ? $this->scopeId : null;
    }

    /**
     *
     * @return string
     */
    public function getAccountStatus() {
        return $this->accountStatus;
    }


}
