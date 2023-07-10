<?php


namespace Kiniauth\Services\Security;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Objects\Security\APIKeyRole;
use Kiniauth\Objects\Security\APIKeySummary;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;

class APIKeyService {


    /**
     * List API keys by accountId and optionally project key
     *
     * @param string $accountId
     * @param string $projectKey
     */
    public function listAPIKeys($projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $sql = "WHERE roles.account_id = ?" . ($projectKey ? " AND roles.scope = 'PROJECT' AND roles.scope_id = ?" : "") . " ORDER BY description";
        $params = $projectKey ? [$accountId, $projectKey] : [$accountId];

        return APIKey::filter($sql, $params);
    }


    /**
     * Get the first API Key with the supplied privilege on the passed project
     *
     * @param string $privilegeKey
     * @param string $projectKey
     * @return APIKey
     */
    public function getFirstAPIKeyWithPrivilege($privilegeKey, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $allKeys = $this->listAPIKeys($projectKey, $accountId);
        foreach ($allKeys as $key) {
            foreach ($key->getRoles() as $role) {
                if (in_array($privilegeKey, $role->getPrivileges()))
                    return $key;
            }
        }
        return null;
    }

    /**
     * Create an API key
     *
     * @param string $description
     * @param APIKeyRole[]
     */
    public function createAPIKey($description = "", $roles = []) {
        $newAPIKey = new APIKey($description, $roles);
        $newAPIKey->save();
        return $newAPIKey->getId();
    }


    /**
     * Convenience method for creating an API key for an account and optionally a project.
     * In the case of a project primary key, the
     *
     * @param string $description
     * @param string $projectKey
     * @param string $accountId
     */
    public function createAPIKeyForAccountAndProject($description = "", $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Grab access role id otherwise grant full access
        $accountAccessRoleId = Role::filter("WHERE privileges = ?", '["access"]')[0]->getId() ?? 0;

        // Generate account role
        $roles = [new APIKeyRole(Role::SCOPE_ACCOUNT, $accountId, $projectKey ? $accountAccessRoleId : 0, $accountId)];

        // If need be generate account role
        if ($projectKey) {
            $roles[] = new APIKeyRole(Role::SCOPE_PROJECT, $projectKey, 0, $accountId);
        }

        return $this->createAPIKey($description, $roles);
    }


    /**
     * Update the description for an API key
     *
     * @param $apiKeyId
     * @param $newDescription
     */
    public function updateAPIKeyDescription($apiKeyId, $newDescription) {
        $key = APIKey::fetch($apiKeyId);
        $key->setDescription($newDescription);
        $key->save();
    }


    public function updateAPIKeyScopeRoles() {

    }

    /**
     * Regenerate an existing API key
     *
     * @param $apiKeyId
     * @return APIKeySummary
     */
    public function regenerateAPIKey($apiKeyId) {
        $key = APIKey::fetch($apiKeyId);
        $key->regenerate();
        $key->save();
        return $key;
    }

    /**
     * Suspend an existing API key
     *
     * @param $apiKeyId
     * @return APIKeySummary
     */
    public function suspendAPIKey($apiKeyId) {
        $key = APIKey::fetch($apiKeyId);
        $key->setStatus(User::STATUS_SUSPENDED);
        $key->save();
        return $key;
    }


    /**
     * Reactivate an existing suspended API key
     *
     * @param $apiKeyId
     * @return APIKeySummary
     */
    public function reactivateAPIKey($apiKeyId) {
        $key = APIKey::fetch($apiKeyId);
        $key->setStatus(User::STATUS_ACTIVE);
        $key->save();
        return $key;
    }


    /**
     * Remove an existing API key
     *
     * @param $apiKeyId
     */
    public function removeAPIKey($apiKeyId) {
        $key = APIKey::fetch($apiKeyId);
        $key->remove();
    }

}