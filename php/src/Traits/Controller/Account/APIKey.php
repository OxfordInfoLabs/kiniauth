<?php


namespace Kiniauth\Traits\Controller\Account;


use Kiniauth\Objects\Security\APIKeySummary;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\Security\APIKeyService;
use Kiniauth\Services\Security\RoleService;
use Kiniauth\ValueObjects\Security\ScopeObjectRolesAssignment;

trait APIKey {

    /**
     * @var APIKeyService
     */
    private $apiKeyService;


    /**
     * @var RoleService
     */
    private $roleService;

    /**
     * Construct with API Key service
     *
     * APIKey constructor.
     * @param APIKeyService $apiKeyService
     * @param RoleService $roleService
     */
    public function __construct($apiKeyService, $roleService) {
        $this->apiKeyService = $apiKeyService;
        $this->roleService = $roleService;
    }


    /**
     * List all API keys for the current account, optionally limited to a project.  Returns an array of summary objects
     *
     * @http GET /
     *
     * @param string $projectKey
     *
     * @return \Kiniauth\Objects\Security\APIKey[]
     */
    public function listAPIKeys($projectKey = null) {
        return $this->apiKeyService->listAPIKeys($projectKey);
    }


    /**
     * Get first key with a privilege
     *
     * @http GET /first/$privilegeKey
     *
     * @param $privilegeKey
     * @param $projectKey
     *
     * @return \Kiniauth\Objects\Security\APIKey
     */
    public function getFirstAPIKeyWithPrivilege($privilegeKey, $projectKey = null) {
        return $this->apiKeyService->getFirstAPIKeyWithPrivilege($privilegeKey, $projectKey);
    }


    /**
     * Create an API key using the passed description - returns the new id for the api key
     *
     * @http POST /
     *
     * @param string $description
     * @param string $projectKey
     *
     * @return string
     */
    public function createAPIKey($description = null, $projectKey = null) {
        return $this->apiKeyService->createAPIKeyForAccountAndProject($description, $projectKey);
    }


    /**
     * Update an API key description
     *
     * @http PUT /$id
     *
     * @param $id
     * @param $description
     */
    public function updateAPIKey($id, $description) {
        $this->apiKeyService->updateAPIKeyDescription($id, $description);
    }


    /**
     * Get all account roles for an API key
     *
     * @http GET /roles/$apiKeyId
     *
     * @param $apiKeyId
     * @return array
     */
    public function getAllAPIKeyAccountRoles($apiKeyId) {
        return $this->roleService->getAllAccountRoles(Role::APPLIES_TO_API_KEY, $apiKeyId);
    }

    /**
     * Get all filtered assignable account scope roles
     *
     * @http GET /assignableRoles
     *
     * @param $apiKeyId
     * @param $filterString
     * @param $offset
     * @param $limit
     */
    public function getFilteredAssignableAccountScopeRoles($apiKeyId, $scope, $filterString = "", $offset = 0, $limit = 10) {
        return $this->roleService->getFilteredAssignableAccountScopeRoles(Role::APPLIES_TO_API_KEY, $apiKeyId, $scope, $filterString, $offset, $limit);
    }

    /**
     * Update the roles for a user scope
     *
     * @http POST /updateAPIKeyScope
     *
     * @param ScopeObjectRolesAssignment[] $scopeObjectRolesAssignments
     * @param string $apiKeyId
     */
    public function updateAssignedScopeObjectRolesForUser($scopeObjectRolesAssignments, $apiKeyId) {
        $this->roleService->updateAssignedScopeObjectRoles(Role::APPLIES_TO_API_KEY, $apiKeyId, $scopeObjectRolesAssignments);
    }


    /**
     * Regenerate an API key - reset it's key and secret
     *
     * @http PUT /regenerate
     *
     * @param $id
     * @return APIKeySummary
     */
    public function regenerateAPIKey($id) {
        return $this->apiKeyService->regenerateAPIKey($id);
    }

    /**
     * Suspend an API key
     *
     * @http PUT /suspend
     *
     * @param $id
     * @return APIKeySummary
     */
    public function suspendAPIkey($id) {
        return $this->apiKeyService->suspendAPIKey($id);
    }


    /**
     * Reactivate an API key
     *
     * @http PUT /reactivate
     *
     * @param $id
     * @return APIKeySummary
     */
    public function reactivateAPIKey($id) {
        return $this->apiKeyService->reactivateAPIKey($id);
    }


    /**
     * Remove an API key
     *
     * @http DELETE /
     *
     * @param $id
     */
    public function removeAPIKey($id) {
        $this->apiKeyService->removeAPIKey($id);
    }

}
