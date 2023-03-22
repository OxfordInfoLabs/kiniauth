<?php

namespace Kiniauth\Objects\Account;


use Kiniauth\Objects\Application\Session;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\StringUtils;

/**
 * Main account business object.  Users can belong to one or more accounts.
 *
 * Class Account
 *
 * @table ka_account
 * @generate
 *
 */
class Account extends AccountSummary {


    /**
     * Boolean indicating whether or not this account can create sub accounts.
     *
     * @var boolean
     */
    protected $subAccountsEnabled;

    /**
     * @var \DateTime
     */
    protected $createdDate;


    /**
     * @var mixed
     * @json
     * @sqlType LONGTEXT
     */
    protected $settings;


    // Logged in account constant for default value usage.
    const LOGGED_IN_ACCOUNT = "LOGGED_IN_ACCOUNT";


    /**
     * Construct an account
     *
     * Account constructor.
     */
    public function __construct($name = null, $parentAccountId = 0, $status = self::STATUS_PENDING, $id = null) {
        $this->name = $name;
        $this->parentAccountId = $parentAccountId;
        $this->status = $status;
        $this->accountId = $id;
    }

    /**
     * @param int $accountId
     */
    public function updateAccountId($accountId) {
        $this->accountId = $accountId;
    }


    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function getSubAccountsEnabled() {
        return $this->subAccountsEnabled;
    }

    /**
     * @param bool $subAccountsEnabled
     */
    public function setSubAccountsEnabled($subAccountsEnabled) {
        $this->subAccountsEnabled = $subAccountsEnabled;
    }


    /**
     * @param int $parentAccountId
     */
    public function setParentAccountId($parentAccountId) {
        $this->parentAccountId = $parentAccountId;
    }

    /**
     * @param string $status
     */
    public function setStatus($status) {
        $this->status = $status;
        if ($status == self::STATUS_ACTIVE && !$this->createdDate) {
            $this->createdDate = new \DateTime();
        }
    }


    /**
     * @return \DateTime
     */
    public function getCreatedDate() {
        return $this->createdDate;
    }

    /**
     * @return mixed
     */
    public function getSettings() {
        return $this->settings ?? [];
    }

    /**
     * @param mixed $settings
     */
    public function setSettings($settings) {
        $this->settings = $settings;
    }


    /**
     * Create a summary from this object
     *
     * @return AccountSummary
     */
    public function generateSummary() {
        return new AccountSummary($this->accountId, $this->name, $this->parentAccountId);
    }


}
