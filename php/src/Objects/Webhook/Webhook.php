<?php

namespace Kiniauth\Objects\Webhook;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Traits\Account\AccountProject;

/**
 * @table ka_webhook
 * @generate
 */
class Webhook extends WebhookSummary {
    use AccountProject;

    public function __construct(?WebhookSummary $webHookSummary = null,
                                ?string        $projectKey = null,
                                mixed          $accountId = Account::LOGGED_IN_ACCOUNT) {
        parent::__construct($webHookSummary?->getDescription(),
            $webHookSummary?->getPushUrl(), $webHookSummary?->getMethod(), "application/json",
            $webHookSummary?->getOtherHeaders(),
            $webHookSummary?->getSignWithKeyPairId(),
            $webHookSummary?->getId());

        $this->accountId = $accountId;
        $this->projectKey = $projectKey;

    }


    /**
     * @return WebhookSummary
     */
    public function generateSummary() {
        return new WebhookSummary($this->getDescription(),
            $this->getPushUrl(),
            $this->getMethod(), "application/json", $this->getOtherHeaders(), $this->getSignWithKeyPairId(), $this->getId());
    }
}