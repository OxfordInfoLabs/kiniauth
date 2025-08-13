<?php

namespace Kiniauth\Services\Webhook;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Webhook\Webhook;
use Kiniauth\Objects\Webhook\WebhookSummary;
use Kiniauth\Services\Security\KeyPairService;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinintel\Objects\Feed\PushFeed;
use Kinintel\Objects\Feed\PushFeedSummary;

/**
 * Webhook service
 */
class WebhookService {

    /**
     * Inject required objects
     *
     * @param HttpRequestDispatcher $httpRequestDispatcher
     * @param KeyPairService $keyPairService
     */
    public function __construct(private HttpRequestDispatcher $httpRequestDispatcher,
                                private KeyPairService        $keyPairService) {
    }


    /**
     * Filter push feeds by feed path and limit and offset.
     *
     * @param ?string $search
     * @param ?string $projectKey
     * @param ?int $offset
     * @param ?int $limit
     * @param mixed $accountId
     * @return WebhookSummary[]
     */
    public function filterWebhooks(?string $search = null, ?string $projectKey = null, ?int $offset = 0, ?int $limit = 10, mixed $accountId = Account::LOGGED_IN_ACCOUNT): array {

        // Construct dynamic clauses as required.
        $filters = ["accountId = ?"];
        $params = [$accountId];
        if ($search) {
            $filters[] = "feedPath = ?";
            $params[] = $search;
        }

        if ($projectKey) {
            $filters[] = "projectKey = ?";
            $params[] = $projectKey;
        }

        // Add offset and limits
        $params[] = $limit;
        $params[] = $offset;

        return array_map(function ($webhook) {
            return $webhook->generateSummary();
        },
            Webhook::filter("WHERE " . join(" AND ", $filters) . " LIMIT ? OFFSET ?", $params));


    }

    /**
     * Save a push feed and return the id.
     *
     * @param WebhookSummary $webhookSummary
     * @param ?string $projectKey
     * @param mixed $accountId
     *
     * @return int
     */
    public function saveWebhook(WebhookSummary $webhookSummary, ?string $projectKey = null,
                                mixed          $accountId = Account::LOGGED_IN_ACCOUNT) {

        $webhook = new Webhook($webhookSummary, $projectKey, $accountId);
        $webhook->save();

        return $webhook->getId();

    }

    /**
     * Remove a push feed by id
     *
     * @param int $pushFeedId
     */
    public function removeWebhook(int $webhookId) {

        $pushFeed = Webhook::fetch($webhookId);
        $pushFeed->remove();

    }


    /**
     * Process a webhook using the passed payload
     *
     * @param int $webhookId
     * @param string $payload
     * @return void
     */
    public function processWebhook(int $webhookId, string $payload) {

    }

}