<?php

namespace Kiniauth\ValueObjects\Security;

/**
 * Value objects for easing the definition of scope access groups
 *
 */
class ScopeAccessGroup {

    private string $groupName;

    /**
     * Construct scope access group.  Scope Accesses should be supplied as
     * an indexed array of scope identifiers indexed by scope name.
     *
     * @param string[string] $scopeAccesses
     * @param string $groupName
     * @param bool|null $writeAccess
     * @param bool|null $grantAccess
     * @param \DateTime|null $expiryDate
     */
    public function __construct(
        private array      $scopeAccesses,
        private ?bool      $writeAccess = false,
        private ?bool      $grantAccess = false,
        private ?\DateTime $expiryDate = null) {
        $this->groupName = hash("md5", join(":", array_merge(array_keys($this->scopeAccesses), array_values($this->scopeAccesses))));
    }

    /**
     * @return array
     */
    public function getScopeAccesses(): array {
        return $this->scopeAccesses;
    }

    /**
     * @return string
     */
    public function getGroupName(): string {
        return $this->groupName;
    }

    /**
     * @return bool|null
     */
    public function getWriteAccess(): ?bool {
        return $this->writeAccess;
    }

    /**
     * @return bool|null
     */
    public function getGrantAccess(): ?bool {
        return $this->grantAccess;
    }

    /**
     * @return \DateTime|null
     */
    public function getExpiryDate(): ?\DateTime {
        return $this->expiryDate;
    }


}