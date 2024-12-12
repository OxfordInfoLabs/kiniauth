<?php


namespace Kiniauth\Traits\Account;

use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Account\PublicAccountSummary;

/**
 * Standard account project trait for objects which need the standard accountId / projectNumber pattern
 *
 * Trait AccountProject
 * @package Kiniauth\Traits\Account
 */
trait AccountProject {

    /**
     * @var int
     */
    protected $accountId;


    /**
     * @var string
     */
    protected $projectKey;


    /**
     * @var PublicAccountSummary
     * @manyToOne
     * @parentJoinColumns account_id
     * @readOnly
     */
    protected $accountSummary;


    /**
     * @return int
     */
    public function getAccountId() {
        return $this->accountId;
    }

    /**
     * @param int $accountId
     */
    public function setAccountId($accountId) {
        $this->accountId = $accountId;
    }

    /**
     * @return string
     */
    public function getProjectKey() {
        return $this->projectKey;
    }

    /**
     * @param string $projectKey
     */
    public function setProjectKey($projectKey) {
        $this->projectKey = $projectKey;
    }

    /**
     * @return AccountSummary
     */
    public function getAccountSummary() {
        return $this->accountSummary;
    }

    /**
     * @param ?PublicAccountSummary $accountSummary
     */
    public function setAccountSummary(?PublicAccountSummary $accountSummary): void {
        $this->accountSummary = $accountSummary;
    }


}