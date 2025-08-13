<?php

namespace Kiniauth\Objects\Webhook;

use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_webhook
 */
class WebhookSummary extends ActiveRecord {

    /**
     * @var array
     * @json
     */
    protected array $otherHeaders;


    /**
     * Construct webhook with url, method, headers - optionally signed with key pair id.
     *
     * @param string|null $description
     * @param string|null $pushUrl
     * @param string|null $method
     * @param string $contentType
     * @param array|null $otherHeaders
     * @param int|null $signWithKeyPairId
     */
    public function __construct(protected ?string $description,
                                protected ?string $pushUrl,
                                protected ?string $method = Request::METHOD_POST,
                                protected ?string $contentType = "application/json",
                                ?array            $otherHeaders = [],
                                protected ?int    $signWithKeyPairId = null,
                                protected ?int    $id = null) {
        $this->otherHeaders = $otherHeaders ?? [];
    }

    /**
     * @return int|null
     */
    public function getId(): ?int {
        return $this->id;
    }


    /**
     * @return string|null
     */
    public function getDescription(): ?string {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getPushUrl(): ?string {
        return $this->pushUrl;
    }

    /**
     * @param string|null $pushUrl
     */
    public function setPushUrl(?string $pushUrl): void {
        $this->pushUrl = $pushUrl;
    }

    /**
     * @return string|null
     */
    public function getMethod(): ?string {
        return $this->method;
    }

    /**
     * @param string|null $method
     */
    public function setMethod(?string $method): void {
        $this->method = $method;
    }

    /**
     * @return array
     */
    public function getOtherHeaders(): array {
        return $this->otherHeaders;
    }

    /**
     * @param array $otherHeaders
     */
    public function setOtherHeaders(array $otherHeaders): void {
        $this->otherHeaders = $otherHeaders;
    }


    /**
     * @return int|null
     */
    public function getSignWithKeyPairId(): ?int {
        return $this->signWithKeyPairId;
    }

    /**
     * @param int|null $signWithKeyPairId
     */
    public function setSignWithKeyPairId(?int $signWithKeyPairId): void {
        $this->signWithKeyPairId = $signWithKeyPairId;
    }


}