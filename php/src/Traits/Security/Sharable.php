<?php

namespace Kiniauth\Traits\Security;

use Kiniauth\Objects\Security\ObjectScopeAccess;
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
    public function returnValidObjectScopeAccessesForScope($recipientScope) {
        $scopedObjects = ObjectArrayUtils::filterArrayOfObjectsByMember("recipientScope", $this->objectScopeAccesses ?? [], $recipientScope);
        return array_filter($scopedObjects, function ($object) {
            return !$object->getExpiryDate() || $object->getExpiryDate() >= new \DateTime();
        });
    }


}