<?php


namespace Kiniauth\Objects\Security;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class APIKeySummary
 *
 * @table ka_api_key
 */
class APIKeySummary extends ActiveRecord {

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiSecret;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $status = User::STATUS_ACTIVE;

    /**
     * APIKeySummary constructor.
     *
     * @param int $id
     * @param string $apiKey
     * @param string $apiSecret
     * @param string $description
     * @param string $status
     */
    public function __construct($id, $apiKey, $apiSecret, $description, $status) {
        $this->id = $id;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->description = $description;
        $this->status = $status;
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
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
     * @param string $apiKey
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
     * @param string $apiSecret
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
     * @param string $description
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
     */
    public function setStatus($status) {
        $this->status = $status;
    }


}