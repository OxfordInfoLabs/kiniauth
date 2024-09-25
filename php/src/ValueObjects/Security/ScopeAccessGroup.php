<?php

namespace Kiniauth\ValueObjects\Security;

use Kinikit\Core\Util\ObjectArrayUtils;

/**
 * Value objects for easing the definition of scope access groups
 *
 */
class ScopeAccessGroup {


    /**
     * Construct scope access group.  Scope Accesses should be supplied as
     * an indexed array of scope identifiers indexed by scope name OR
     * full scope access item objects containing an id and a string identifier.
     *
     * @param ScopeAccessItem[] $scopeAccesses
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


    }

    /**
     * @return ScopeAccessItem[]
     */
    public function getScopeAccesses(): array {
        return $this->scopeAccesses;
    }


    /**
     * Add a scope access item
     *
     * @param $scopeAccessItem
     * @return void
     */
    public function addScopeAccess(ScopeAccessItem $scopeAccessItem) {
        $this->scopeAccesses[] = $scopeAccessItem;
    }


    /**
     * @return string
     */
    public function getGroupName(): string {
        // Scopes
        $scopeAccessScopes = ObjectArrayUtils::getMemberValueArrayForObjects("scope", $this->scopeAccesses);
        $scopeAccessIdentifiers = ObjectArrayUtils::getMemberValueArrayForObjects("itemIdentifier", $this->scopeAccesses);
        return hash("md5", join(":", array_merge($scopeAccessScopes, $scopeAccessIdentifiers)));

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


    /**
     * Get expiry date as a string
     *
     * @return string
     */
    public function getExpiryDateString(): string {
        return $this->expiryDate ? $this->expiryDate->format("Y-m-d") : "";
    }

}