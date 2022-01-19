<?php


namespace Kiniauth\Services\Security;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Objects\Security\APIKeySummary;
use Kiniauth\Objects\Security\User;

class APIKeyService {


    /**
     * List API keys
     *
     * @param string $accountId
     * @param string $projectKey
     */
    public function listAPIKeys($projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $sql = "WHERE account_id = ?" . ($projectKey ? " AND project_key = ?" : "") . " ORDER BY description";
        $params = $projectKey ? [$accountId, $projectKey] : [$accountId];

        return APIKeySummary::filter($sql, $params);
    }


    /**
     * Create an API key
     *
     * @param string $description
     * @param string $accountId
     * @param string $projectKey
     */
    public function createAPIKey($description = "", $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $newAPIKey = new APIKey($description, $accountId, $projectKey);
        $newAPIKey->save();
        return $newAPIKey->getId();
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


    /**
     * Regenerate an existing API key
     *
     * @param $apiKeyId
     */
    public function regenerateAPIKey($apiKeyId) {
        $key = APIKey::fetch($apiKeyId);
        $key->regenerate();
        $key->save();
    }

    /**
     * Suspend an existing API key
     *
     * @param $apiKeyId
     */
    public function suspendAPIKey($apiKeyId) {
        $key = APIKey::fetch($apiKeyId);
        $key->setStatus(User::STATUS_SUSPENDED);
        $key->save();
    }


    /**
     * Reactivate an existing suspended API key
     *
     * @param $apiKeyId
     */
    public function reactivateAPIKey($apiKeyId) {
        $key = APIKey::fetch($apiKeyId);
        $key->setStatus(User::STATUS_ACTIVE);
        $key->save();
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