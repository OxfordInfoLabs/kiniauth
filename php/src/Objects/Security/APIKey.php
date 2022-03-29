<?php


namespace Kiniauth\Objects\Security;

use Kinikit\Core\Util\StringUtils;

/**
 * Class APIKey
 *
 * @table ka_api_key
 * @generate
 */
class APIKey extends Securable {

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $accountId;

    /**
     * @var string
     */
    private $projectKey;

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
     * APIKey constructor.
     *
     * @param string $description
     * @param string $accountId
     * @param string $projectKey
     */
    public function __construct($description, $accountId, $projectKey) {
        $this->description = $description;
        $this->accountId = $accountId;
        $this->projectKey = $projectKey;

        $this->regenerate();
    }

    
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
     * @return UserRole[]
     */
    public function getRoles() {

        // Create account role
        $accountRole = new UserRole(Role::SCOPE_ACCOUNT, $this->accountId, -1, $this->accountId);
        $accountRole->setRole(new Role(Role::SCOPE_ACCOUNT, "API Key Account Role", "API Key Account Role", [
            $this->projectKey ? "access" : "*"
        ]));

        $roles = [$accountRole];

        // If project key, add role for this.
        if ($this->projectKey) {
            $projectRole = new UserRole("PROJECT", $this->projectKey, -2, $this->accountId);
            $projectRole->setRole(new Role("PROJECT", "API Key Project Role", "API Key Project Role", [
                "*"
            ]));
            $roles[] = $projectRole;
        }

        return $roles;


    }


    /**
     * Always return defined account id as API keys cannot cross accounts.
     *
     * @return int
     */
    public function getActiveAccountId() {
        return $this->accountId;
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


}