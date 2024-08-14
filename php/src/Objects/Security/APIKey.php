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
class APIKey extends Securable {

    /**
     * @var integer
     */
    private ?int $id;

    /**
     * @var string
     */
    private ?string $apiKey;

    /**
     * @var string
     */
    private ?string $apiSecret;

    /**
     * @var string
     */
    private ?string $description;

    /**
     * An array of explicit role objects
     *
     * @oneToMany
     * @childJoinColumns api_key_id
     * @var APIKeyRole[]
     */
    protected $roles = [];

    /**
     * @var string
     */
    private ?string $status;

    /**+
     * @var string
     */
    private string $type;

    /**
     * @var array
     * @sqlType LONGTEXT
     * @json
     */
    private array $config;

    /**
     * @var int
     */
    private ?int $whitelistedIpRangeProfileId;


    const TYPE_PUBLIC = "public";
    const TYPE_CAPTCHA = "captcha";
    const TYPE_WHITELISTED = "whitelisted";

    /**
     * APIKey constructor.
     *
     * @param string $description
     * @param APIKeyRole[] $roles
     * @param string $apiKey
     * @param string $apiSecret
     * @param string $status
     * @param int $id
     * @param string $type
     * @param array $config
     * @param int $whitelistedIpRangeProfileId
     */
    public function __construct(?string $description, array $roles = [], ?string $apiKey = null, ?string $apiSecret = null, ?string $status = null,
                                int $id = null, string $type = self::TYPE_PUBLIC, array $config = [], ?int $whitelistedIpRangeProfileId = null) {
        $this->description = $description;
        $this->roles = $roles;
        $this->status = $status ?? UserSummary::STATUS_ACTIVE;
        $this->id = $id;
        $this->type = $type;
        $this->config = $config;
        $this->whitelistedIpRangeProfileId = $whitelistedIpRangeProfileId;

        if ($apiKey) {
            $this->apiKey = $apiKey;
            $this->apiSecret = $apiSecret;
        } else
            $this->regenerate();
    }


    /**
     * @return int
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getApiKey(): ?string {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey(?string $apiKey): void {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getApiSecret(): ?string {
        return $this->apiSecret;
    }

    /**
     * @param string $apiSecret
     */
    public function setApiSecret(?string $apiSecret): void {
        $this->apiSecret = $apiSecret;
    }


    public function getAccountIds(): array {
        $accountIds = [];
        foreach ($this->roles as $role) {
            if ($role->getAccountId())
                $accountIds[$role->getAccountId()] = 1;
        }
        return array_keys($accountIds);
    }


    /**
     * Always return defined account id as API keys cannot cross accounts.
     *
     * @return int|null
     */
    public function getActiveAccountId(): ?int {
        return ObjectArrayUtils::getMemberValueArrayForObjects("accountId", $this->getRoles())[0] ?? null;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(?string $description): void {
        $this->description = $description;
    }


    /**
     * API keys are always active.
     *
     * @return string
     */
    public function getStatus(): ?string {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(?string $status): void {
        $this->status = $status;
    }

    public function getType(): ?string {
        return $this->type;
    }

    public function setType(?string $type): void {
        $this->type = $type;
    }

    public function getConfig(): array {
        return $this->config;
    }

    public function setConfig(array $config): void {
        $this->config = $config;
    }

    public function getWhitelistedIpRangeProfileId(): ?int {
        return $this->whitelistedIpRangeProfileId;
    }

    public function setWhitelistedIpRangeProfileId(?int $whitelistedIpRangeProfileId): void {
        $this->whitelistedIpRangeProfileId = $whitelistedIpRangeProfileId;
    }

    public function returnWhitelistedProfile(): ?WhitelistedIPRangeProfile {
        if ($id = $this->getWhitelistedIpRangeProfileId())
            return WhitelistedIPRangeProfile::fetch($id);
        return null;
    }

    /**
     * Regenerate key and secret
     */
    public function regenerate(): void {
        $this->apiKey = StringUtils::generateRandomString(16);
        $this->apiSecret = StringUtils::generateRandomString(16);
    }


}