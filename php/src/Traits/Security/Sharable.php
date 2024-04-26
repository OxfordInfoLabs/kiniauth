<?php

namespace Kiniauth\Traits\Security;

use Kiniauth\Objects\Security\ObjectScopeAccess;
use Kiniauth\ValueObjects\Security\ScopeAccessGroup;
use Kiniauth\ValueObjects\Security\ScopeAccessItem;
use Kinikit\Core\Util\ObjectArrayUtils;

/**
 * Sharable trait - may be added to an object which we want to be sharable with other
 * entities e.g. Accounts etc.
 */
trait Sharable {

    /**
     * Array of shared objects to attach to the entity using this trait.
     *
     * @var ObjectScopeAccess[]
     * @readOnly
     * @oneToMany
     * @childJoinColumns shared_object_primary_key,shared_object_class_name=CLASSNAME
     */
    private ?array $objectScopeAccesses = [];


    /**
     * Get the recipient pk objects for a given scope
     *
     * @param string $recipientScope
     * @return string[]
     */
    public function returnValidObjectScopeAccesses($recipientScope = null) {
        $scopedObjects = $recipientScope ? ObjectArrayUtils::filterArrayOfObjectsByMember("recipientScope", $this->objectScopeAccesses ?? [], $recipientScope) :
            $this->objectScopeAccesses;
        return array_filter($scopedObjects, function ($object) {
            return !$object->getExpiryDate() || $object->getExpiryDate() >= new \DateTime();
        });
    }


    /**
     * @return ScopeAccessGroup[]
     */
    public function returnValidScopeAccessGroups() {
        $accessGroups = [];
        foreach ($this->objectScopeAccesses ?? [] as $item) {

            // If expired, skip this group
            if ($item->getExpiryDate() && $item->getExpiryDate() < new \DateTime())
                continue;


            // Ensure group exists
            if (!isset($accessGroups[$item->getAccessGroup()])) {
                $accessGroups[$item->getAccessGroup()] = new ScopeAccessGroup([], $item->getWriteAccess(), $item->getGrantAccess(), $item->getExpiryDate());
            }

            $accessGroups[$item->getAccessGroup()]->addScopeAccess(new ScopeAccessItem($item->getRecipientScope(), $item->getRecipientPrimaryKey()));

        }
        return array_values($accessGroups);
    }


}