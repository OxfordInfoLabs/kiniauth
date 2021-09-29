<?php


namespace Kiniauth\Objects\Security;


/**
 * Class WhitelistedIPRange
 * @package Kiniauth\Objects\Security
 *
 * @table ka_whitelisted_ip_range
 * @generate
 */
class WhitelistedIPRange {

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $accountId;


    /**
     * @var integer
     */
    private $userId;


    /**
     * @var integer
     */
    private $apiKeyId;


    /**
     * @var string
     */
    private $ipv4AddressRangeString;

    /**
     * @var string
     */
    private $ipv6AddressRangeString;

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

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
     * @return int
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId) {
        $this->userId = $userId;
    }

    /**
     * @return int
     */
    public function getApiKeyId() {
        return $this->apiKeyId;
    }

    /**
     * @param int $apiKeyId
     */
    public function setApiKeyId($apiKeyId) {
        $this->apiKeyId = $apiKeyId;
    }

    /**
     * @return string
     */
    public function getIpv4AddressRangeString() {
        return $this->ipv4AddressRangeString;
    }

    /**
     * @param string $ipv4AddressRangeString
     */
    public function setIpv4AddressRangeString($ipv4AddressRangeString) {
        $this->ipv4AddressRangeString = $ipv4AddressRangeString;
    }

    /**
     * @return string
     */
    public function getIpv6AddressRangeString() {
        return $this->ipv6AddressRangeString;
    }

    /**
     * @param string $ipv6AddressRangeString
     */
    public function setIpv6AddressRangeString($ipv6AddressRangeString) {
        $this->ipv6AddressRangeString = $ipv6AddressRangeString;
    }


    /**
     * Return a boolean indicating whether or not the passed address is whitelisted
     * using the defined rules.  Handles both ipv4 and ipv6 addresses
     */
    public function isAddressWhitelisted($address) {

    }

}