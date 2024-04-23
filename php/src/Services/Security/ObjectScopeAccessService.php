<?php

namespace Kiniauth\Services\Security;


use Kiniauth\Exception\Security\NoObjectGrantAccessException;
use Kiniauth\Exception\Security\ObjectNotSharableException;
use Kiniauth\Objects\Security\ObjectScopeAccess;
use Kiniauth\Traits\Security\Sharable;
use Kiniauth\ValueObjects\Security\ScopeAccessGroup;
use Kinikit\Core\Reflection\ClassInspector;
use Kinikit\Persistence\ORM\ORM;

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
        private SecurityService $securityService,
        private ORM             $orm
    ) {
    }


    /**
     * Assign one or more scope access groups to an object.  This will effectively replace any existing entries for the
     * passed groups.
     *
     * @param string $objectClassName
     * @param string $objectPrimaryKey
     * @param ScopeAccessGroup[] $scopeAccessGroups
     * @return void
     */
    public function assignScopeAccessGroupsToObject(string $objectClassName, string $objectPrimaryKey, array $scopeAccessGroups) {

        // Check that the class being assigned is sharable
        $classInspector = new ClassInspector($objectClassName);
        if (!$classInspector->usesTrait(Sharable::class))
            throw new ObjectNotSharableException($objectClassName);

        // Grab the object using the ORM
        $object = $this->orm->fetch($objectClassName, $objectPrimaryKey);
        if (!$this->securityService->checkLoggedInObjectAccess($object, SecurityService::ACCESS_GRANT))
            throw new NoObjectGrantAccessException($objectClassName, $objectPrimaryKey);

        // Save all object scope accesses
        foreach ($scopeAccessGroups as $group) {
            foreach ($group->getScopeAccesses() as $scope => $scopeId) {
                $objectScopeAccess = new ObjectScopeAccess($scope, $scopeId, $group->getGroupName(), $group->getWriteAccess(), $group->getGrantAccess(), $group->getExpiryDate(), $objectClassName, $objectPrimaryKey);
                $objectScopeAccess->save();
            }
        }


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

        // Grab the object using the ORM
        $object = $this->orm->fetch($objectClassName, $objectPrimaryKey);
        if (!$this->securityService->checkLoggedInObjectAccess($object, SecurityService::ACCESS_GRANT))
            throw new NoObjectGrantAccessException($objectClassName, $objectPrimaryKey);


        // Remove all entries for the specified access groups
        foreach ($accessGroups as $accessGroup) {
            $matchingScopes = ObjectScopeAccess::filter("WHERE accessGroup = ?", $accessGroup);
            foreach ($matchingScopes as $scope) {
                $scope->remove();
            }
        }


    }


}