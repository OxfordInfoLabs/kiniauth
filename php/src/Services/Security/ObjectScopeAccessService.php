<?php

namespace Kiniauth\Services\Security;


use Kiniauth\ValueObjects\Security\ScopeAccessGroup;

/**
 * Service for managing particularly creation and management of object scope accesses.
 */
class ObjectScopeAccessService {

    /**
     * Construct with security service for resolving permissions
     *
     * @param SecurityService $securityService
     */
    public function __construct(
        private SecurityService $securityService
    ) {
    }


    /**
     * @param string $objectClassName
     * @param string $objectPrimaryKey
     * @param ScopeAccessGroup[] $scopeAccessGroups
     * @param bool|null $writeAccess
     * @param bool|null $grantAccess
     * @param \DateTime|null $expiryDate
     * @return void
     */
    public function assignScopeAccessGroupsToObject(string $objectClassName, string $objectPrimaryKey, array $scopeAccessGroups, ?bool $writeAccess = false,
                                                    ?bool  $grantAccess = false, ?\DateTime $expiryDate = null) {

    }

    /**
     * Remove scope access groups from an object
     *
     * @param string $objectClassName
     * @param string $objectPrimaryKey
     * @param array $accessGroups
     * @return void
     */
    public function removeScopeAccessGroupsFromObject(string $objectClassName, string $objectPrimaryKey, array $accessGroups) {

    }


}