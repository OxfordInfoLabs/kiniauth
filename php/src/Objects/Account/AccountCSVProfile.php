<?php

namespace Kiniauth\Objects\Account;

use Kiniauth\Traits\Account\AccountProject;

/**
 * @table ka_account_csv_profile
 * @generate
 */
class AccountCSVProfile extends AccountCSVProfileSummary {

    use AccountProject;

    /**
     * AccountCSVProfile constructor.
     * @param AccountCSVProfileSummary $accountCSVProfileSummary
     * @param ?string $projectKey
     * @param integer $accountId
     */
    public function __construct($accountCSVProfileSummary, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        if ($accountCSVProfileSummary) {
            parent::__construct(
                $accountCSVProfileSummary->getMapping(),
                $accountCSVProfileSummary->getExtraDataFlags(),
                $accountCSVProfileSummary->getId()
            );
        }
        $this->projectKey = $projectKey;
        $this->accountId = $accountId;
    }

    /**
     * Return a summary object
     */
    public function returnSummary(): AccountCSVProfileSummary {
        return new AccountCSVProfileSummary(
            $this->getMapping(),
            $this->getExtraDataFlags(),
            $this->getId()
        );
    }

}