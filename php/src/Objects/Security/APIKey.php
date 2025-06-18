<?php


namespace Kiniauth\Objects\Security;

use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Core\Util\StringUtils;

/**
 * Class APIKey
 *
 * @table ka_api_key
 * @generate
 */
class APIKey extends APIKeySummary {

    /**
     * @var integer
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
     * An array of explicit role objects
     *
     * @oneToMany
     * @childJoinColumns api_key_id
     * @var APIKeyRole[]
     */
    protected $roles = array();


    /**
     * @var string
     */
    protected $status = User::STATUS_ACTIVE;


    /**
     * APIKey constructor.
     *
     * @param string $description
     * @param APIKeyRole[] $roles
     */
    public function __construct($description, $roles = [], $apiKey = null, $apiSecret = null, $status = null, $id = null) {
        $this->description = $description;
        $this->roles = $roles;
        $this->status = $status ?? User::STATUS_ACTIVE;
        $this->id = $id;

        if ($apiKey) {
            $this->apiKey = $apiKey;
            $this->apiSecret = $apiSecret;
        } else
            $this->regenerate();
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
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


    public function getAccountIds() {
        $accountIds = array();
        foreach ($this->roles as $role) {
            if ($role->getAccountId())
                $accountIds[$role->getAccountId()] = 1;
        }
        return array_keys($accountIds);
    }


    /**
     * Always return defined account id as API keys cannot cross accounts.
     *
     * @return int
     */
    public function getActiveAccountId() {
        return ObjectArrayUtils::getMemberValueArrayForObjects("accountId", $this->getRoles())[0] ?? null;
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
     * API keys are always active.
     *
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

    /**
     * Regenerate key and secret
     */
    public function regenerate() {
        $this->apiKey = StringUtils::generateRandomString(16);
        $this->apiSecret = StringUtils::generateRandomString(16);
    }

    public function generateSummary() {
        return new APIKeySummary(
            $this->id,
            $this->apiKey,
            $this->apiSecret,
            $this->description,
            $this->status
        );
    }

}