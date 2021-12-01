<?php


namespace Kiniauth\Objects\Security;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_api_key_role
 * @generate
 */
class APIKeyRole extends ActiveRecord {

    /**
     * The API Key for which this account role is being attached
     *
     * @var integer
     * @primaryKey
     */
    private $apiKeyId;

    /**
     * The scope of this role (defaults to account)
     *
     * @var string
     * @primaryKey
     * @maxLength 50
     */
    private $scope;

    /**
     * The id of the scope object for which this role is being attached.  If set to blank
     * this is assumed to refer to all objects
     *
     * @var string
     * @primaryKey
     */
    private $scopeId;

    /**
     * The role id for this api key role.
     *
     * @var integer
     * @primaryKey
     */
    private $roleId;

    /**
     * An optional account id which should be set if the role scope is ACCOUNT
     * or if the scope being limited represents sub objects within an account for
     * increased security.
     *
     * @var integer
     */
    private $accountId;


    /**
     * The role object for this api key role
     *
     * @manyToOne
     * @parentJoinColumns role_id
     * @readOnly
     *
     * @var Role
     */
    protected $role;


    /**
     * Construct a new api key account role object.
     *
     * @param string $scope
     * @param string $scopeId
     * @param integer $roleId
     * @param integer $userId
     */
    public function __construct($scope = Role::SCOPE_ACCOUNT, $scopeId = null, $roleId = null, $accountId = null, $apiKeyId = null) {
        $this->scope = $scope;
        $this->scopeId = $scopeId;
        $this->roleId = $roleId;
        $this->accountId = $accountId;
        $this->apiKeyId = $apiKeyId;
    }

    /**
     * @return int
     */
    public function getApiKeyId() {
        return $this->apiKeyId;
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
     * @return int
     */
    public function getAccountId() {
        return $this->accountId;
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


}