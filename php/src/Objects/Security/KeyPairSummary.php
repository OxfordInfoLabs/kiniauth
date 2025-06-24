<?php

namespace Kiniauth\Objects\Security;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_key_pair
 */
class KeyPairSummary extends ActiveRecord {


    /**
     * @var string
     * @sqlType LONGTEXT
     */
    protected ?string $privateKey = null;

    /**
     * @var string
     * @sqlType LONGTEXT
     */
    protected ?string $publicKey = null;

    /**
     * Construct keypair class
     *
     * @param int $id
     * @param string $description
     * @param string $privateKey
     * @param string $publicKey
     */
    public function __construct(protected ?string $description,
                                ?string $privateKey, ?string $publicKey, protected ?int $id = null) {
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
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
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): ?string {
        return $this->privateKey;
    }

    /**
     * @param string $privateKey
     */
    public function setPrivateKey(string $privateKey): void {
        $this->privateKey = $privateKey;
    }

    /**
     * @return string
     */
    public function getPublicKey(): ?string {
        return $this->publicKey;
    }

    /**
     * @param string $publicKey
     */
    public function setPublicKey(string $publicKey): void {
        $this->publicKey = $publicKey;
    }

}