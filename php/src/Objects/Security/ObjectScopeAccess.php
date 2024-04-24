<?php

namespace Kiniauth\Objects\Security;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_object_scope_access
 * @generate
 *
 * Shared objects allow for objects which are usually locked to a specific scope
 * e.g. a single account object to be shared with other accounts.
 *
 */
class ObjectScopeAccess extends ActiveRecord {

    /**
     * Fully qualified classname of object being shared
     *
     * @primaryKey
     */
    private ?string $sharedObjectClassName;


    /**
     * Primary key of the object being shared
     *
     * @primaryKey
     * @maxLength 25
     */
    private ?string $sharedObjectPrimaryKey;


    /**
     * Scope for the recipient of the shared object e.g. ACCOUNT
     *
     * @primaryKey
     * @maxLength 25
     */
    private ?string $recipientScope;


    /**
     * @primaryKey
     * @maxLength 25
     */
    private ?string $recipientPrimaryKey;


    /**
     * Access group - for grouping accesses together logically.
     * @primaryKey
     * @maxLength 80
     */
    private ?string $accessGroup;


    /**
     * Indicator as to whether write access is enabled for this scope.
     */
    private ?bool $writeAccess = false;


    /**
     *  Indicator as to whether grant access is enabled for this scope.
     */
    private ?bool $grantAccess = false;


    /**
     * Expiry date of the object scope if relevant
     */
    private ?\DateTime $expiryDate;



    /**
     * @param string $recipientScope
     * @param string $recipientPrimaryKey
     * @param string $accessGroup
     * @param bool $writeAccess
     * @param bool $grantAccess
     * @param \DateTime $expiryDate
     * @param string $sharedObjectClassName
     * @param string $sharedObjectPrimaryKey
     */
    public function __construct(?string $recipientScope, ?string $recipientPrimaryKey, ?string $accessGroup, ?bool $writeAccess = false, ?bool $grantAccess = false, ?\DateTime $expiryDate = null, ?string $sharedObjectClassName = null, ?string $sharedObjectPrimaryKey = null) {
        $this->recipientScope = $recipientScope;
        $this->recipientPrimaryKey = $recipientPrimaryKey;
        $this->accessGroup = $accessGroup;
        $this->writeAccess = $writeAccess;
        $this->grantAccess = $grantAccess;
        $this->expiryDate = $expiryDate;
        $this->sharedObjectClassName = $sharedObjectClassName;
        $this->sharedObjectPrimaryKey = $sharedObjectPrimaryKey;
        $this->accessGroup = $accessGroup;
    }

    /**
     * @return string
     */
    public function getSharedObjectClassName(): ?string {
        return $this->sharedObjectClassName;
    }

    /**
     * @param string $sharedObjectClassName
     */
    public function setSharedObjectClassName(?string $sharedObjectClassName): void {
        $this->sharedObjectClassName = $sharedObjectClassName;
    }

    /**
     * @return string
     */
    public function getSharedObjectPrimaryKey(): ?string {
        return $this->sharedObjectPrimaryKey;
    }

    /**
     * @param string $sharedObjectPrimaryKey
     */
    public function setSharedObjectPrimaryKey(?string $sharedObjectPrimaryKey): void {
        $this->sharedObjectPrimaryKey = $sharedObjectPrimaryKey;
    }

    /**
     * @return string
     */
    public function getRecipientScope(): string {
        return $this->recipientScope;
    }

    /**
     * @param string $recipientScope
     */
    public function setRecipientScope(string $recipientScope): void {
        $this->recipientScope = $recipientScope;
    }

    /**
     * @return string
     */
    public function getRecipientPrimaryKey(): ?string {
        return $this->recipientPrimaryKey;
    }

    /**
     * @param string $recipientPrimaryKey
     */
    public function setRecipientPrimaryKey(?string $recipientPrimaryKey): void {
        $this->recipientPrimaryKey = $recipientPrimaryKey;
    }

    /**
     * @return string
     */
    public function getAccessGroup(): ?string {
        return $this->accessGroup;
    }

    /**
     * @param string $accessGroup
     */
    public function setAccessGroup(?string $accessGroup): void {
        $this->accessGroup = $accessGroup;
    }


    /**
     * @return bool|null
     */
    public function getWriteAccess(): ?bool {
        return $this->writeAccess;
    }

    /**
     * @param bool|null $writeAccess
     */
    public function setWriteAccess(?bool $writeAccess): void {
        $this->writeAccess = $writeAccess;
    }

    /**
     * @return bool
     */
    public function getGrantAccess(): ?bool {
        return $this->grantAccess;
    }

    /**
     * @param bool $grantAccess
     */
    public function setGrantAccess(?bool $grantAccess): void {
        $this->grantAccess = $grantAccess;
    }

    /**
     * @return \DateTime
     */
    public function getExpiryDate(): ?\DateTime {
        return $this->expiryDate;
    }

    /**
     * @param \DateTime $expiryDate
     */
    public function setExpiryDate(?\DateTime $expiryDate): void {
        $this->expiryDate = $expiryDate;
    }


}