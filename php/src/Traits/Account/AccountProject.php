<?php


namespace Kiniauth\Traits\Account;

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


}