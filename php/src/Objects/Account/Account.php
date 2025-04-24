<?php

namespace Kiniauth\Objects\Account;


use Kiniauth\Attributes\Security\AccessNonActiveScopes;
use Kiniauth\Objects\Application\Session;
use Kiniauth\Objects\Security\AccountRole;
use Kiniauth\Objects\Security\Privilege;

/**
 * Main account business object.  Users can belong to one or more accounts.
 *
 * Class Account
 *
 * @table ka_account
 * @generate
 *
 */
#[AccessNonActiveScopes]
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


    /**
     * An array of explicit privileges which this account has access to
     * This is used to limit user access.  This should be encoded as arrays
     * of privilege keys indexed by SCOPE
     *
     * @var string[]
     * @json
     */
    protected $privileges = array();


    /**
     * @var boolean
     */
    protected $discoverable;


    /**
     * String identifier for identifying this account for e.g. sharing etc.
     *
     * @var string
     */
    protected $externalIdentifier;


    /**
     * @var AccountSecurityDomain[]
     *
     * @oneToMany
     * @childJoinColumns account_id
     */
    protected ?array $securityDomains = [];


    // Logged in account constant for default value usage.
    const LOGGED_IN_ACCOUNT = "LOGGED_IN_ACCOUNT";


    /**
     * Construct an account
     *
     * Account constructor.
     */
    public function __construct($name = null, $parentAccountId = 0, $status = self::STATUS_PENDING, $id = null, $securityDomains = []) {
        $this->name = $name;
        $this->parentAccountId = $parentAccountId;
        $this->status = $status;
        $this->accountId = $id;
        $this->securityDomains = $securityDomains;
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
     * @param string $logo
     * @return void
     */
    public function setLogo($logo) {
        $this->logo = $logo;
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
     * @return string[]
     */
    public function getPrivileges() {
        return $this->privileges;
    }

    /**
     * @param string[] $privileges
     */
    public function setPrivileges($privileges) {
        $this->privileges = $privileges;
    }

    /**
     * @return string
     */
    public function getExternalIdentifier() {
        return $this->externalIdentifier;
    }

    /**
     * @param string $externalIdentifier
     */
    public function setExternalIdentifier($externalIdentifier) {
        $this->externalIdentifier = $externalIdentifier;
    }

    /**
     * @return bool
     */
    public function isDiscoverable() {
        return $this->discoverable;
    }

    /**
     * @param bool $discoverable
     */
    public function setDiscoverable($discoverable) {
        $this->discoverable = $discoverable;
    }

    /**
     * @return AccountSecurityDomain[]
     */
    public function getSecurityDomains(): ?array {
        return $this->securityDomains;
    }

    /**
     * @param AccountSecurityDomain[] $securityDomains
     */
    public function setSecurityDomains(?array $securityDomains): void {
        $this->securityDomains = $securityDomains;
    }


    /**
     * Return the account roles, used by the scope access objects and designed to be overloaded if required.
     *
     * @return Privilege[]
     */
    public function returnAccountPrivileges() {
        return $this->privileges ?? [];
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
