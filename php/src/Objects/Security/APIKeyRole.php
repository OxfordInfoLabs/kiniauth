<?php


namespace Kiniauth\Objects\Security;

/**
 * API Key Role mapping
 *
 * @table ka_api_key_role
 * @generate
 */
class APIKeyRole extends SecurableRole {

    /**
     * @var integer
     * @primaryKey
     */
    private $apiKeyId;


    /**
     * Construct a new user account role object.
     *
     * @param string $scope
     * @param string $scopeId
     * @param integer $roleId
     * @param integer $userId
     */
    public function __construct($scope = Role::SCOPE_ACCOUNT, $scopeId = null, $roleId = null, $accountId = -1, $apiKeyId = null) {
        parent::__construct($scope, $scopeId, $roleId, $accountId);
        $this->apiKeyId = $apiKeyId;
    }


    /**
     * @return int
     */
    public function getApiKeyId() {
        return $this->apiKeyId;
    }


}