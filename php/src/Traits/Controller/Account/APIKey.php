<?php


namespace Kiniauth\Traits\Controller\Account;


use Kiniauth\Objects\Security\APIKeySummary;
use Kiniauth\Services\Security\APIKeyService;

trait APIKey {

    /**
     * @var APIKeyService
     */
    private $apiKeyService;

    /**
     * Construct with API Key service
     *
     * APIKey constructor.
     * @param APIKeyService $apiKeyService
     */
    public function __construct($apiKeyService) {
        $this->apiKeyService = $apiKeyService;
    }


    /**
     * List all API keys for the current account, optionally limited to a project.  Returns an array of summary objects
     *
     * @http GET /
     *
     * @param string $projectKey
     *
     * @return APIKeySummary[]
     */
    public function listAPIKeys($projectKey = null) {
        return $this->apiKeyService->listAPIKeys($projectKey);
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
        return $this->apiKeyService->createAPIKey($description, $projectKey);
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
