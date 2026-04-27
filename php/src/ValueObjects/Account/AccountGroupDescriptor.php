<?php

namespace Kiniauth\ValueObjects\Account;

class AccountGroupDescriptor {

    /**
     * @var string
     */
    private ?string $name;

    /**
     * @var string
     */
    private ?string $description;

    /**
     * @var string
     */
    private ?string $type;

    /**
     * @var int
     */
    private ?int $ownerAccountId;

    /**
     * @param string $name
     * @param string $description
     * @param string $type
     * @param int $ownerAccountId
     */
    public function __construct(?string $name = null, ?string $description = null, ?string $type, ?int $ownerAccountId = null) {
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->ownerAccountId = $ownerAccountId;
    }

    /**
     * @return string
     */
    public function getName(): ?string {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName(?string $name): void {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string {
        return $this->description;
    }

    /**
     * @param string $description
     * @return void
     */
    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getType(): ?string {
        return $this->type;
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType(?string $type): void {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getOwnerAccountId(): ?int {
        return $this->ownerAccountId;
    }

    /**
     * @param int $ownerAccountId
     * @return void
     */
    public function setOwnerAccountId(?int $ownerAccountId): void {
        $this->ownerAccountId = $ownerAccountId;
    }

}