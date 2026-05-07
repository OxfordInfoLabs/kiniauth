<?php

namespace Kiniauth\Services\Workflow\Task\Scheduled;

use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Workflow\Task\Task;

class AccountExpiryTask implements Task {
    public function __construct(private AccountService $accountService) {
    }

    /**
     * @inheritDoc
     */
    public function run($configuration) {
        $this->accountService->processAccountExpiries();
    }
}