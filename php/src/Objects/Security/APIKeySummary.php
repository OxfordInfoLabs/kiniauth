<?php

namespace Kiniauth\Objects\Security;

class APIKeySummary extends Securable {

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $apiSecret;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $status;

    /**
     * @param $apiKey
     * @param $apiSecret
     * @param $description
     * @param $status
     */
    public function __construct($id = null, $apiKey = null, $apiSecret = null, $description = null, $status = null) {
        $this->id = $id;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->description = $description;
        $this->status = $status;
    }

    /**
     * @param int $id
     * @return void
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getApiKey() {
        return $this->apiKey;
    }

    /**
     * @param string $status
     * @return void
     */
    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getApiSecret() {
        return $this->apiSecret;
    }

    /**
     * @param string $status
     * @return void
     */
    public function setApiSecret($apiSecret) {
        $this->apiSecret = $apiSecret;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param string $status
     * @return void
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param string $status
     * @return void
     */
    public function setStatus($status) {
        $this->status = $status;
    }

    /**
     * @return null
     */
    public function getActiveAccountId() {
        return null;
    }
}