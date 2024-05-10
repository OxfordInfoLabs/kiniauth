<?php


namespace Kiniauth\Services\Security;


use Kiniauth\Objects\Security\ObjectScopeAccess;
use Kiniauth\Traits\Security\Sharable;
use Kinikit\Core\Reflection\ClassInspectorProvider;

class ScopeManager {

    /**
     * @var ScopeAccess[]
     */
    private $scopeAccesses = [];

    /**
     * @var ClassInspectorProvider
     */
    private $classInspectorProvider;


    /**
     * ScopeManager constructor.
     *
     * @param AccountScopeAccess $accountScopeAccess
     * @param ProjectScopeAccess $projectScopeAccess
     * @param ClassInspectorProvider $classInspectorProvider
     */
    public function __construct($accountScopeAccess, $projectScopeAccess, $classInspectorProvider) {
        $this->scopeAccesses[$accountScopeAccess->getScope()] = $accountScopeAccess;
        $this->scopeAccesses[$projectScopeAccess->getScope()] = $projectScopeAccess;
        $this->classInspectorProvider = $classInspectorProvider;
    }


    /**
     * Add a scope access to the array of scope accesses.
     *
     * @param ScopeAccess $scopeAccess
     */
    public function addScopeAccess($scopeAccess) {
        $this->scopeAccesses[$scopeAccess->getScope()] = $scopeAccess;
    }

    /**
     * Get the scope access for a given scope
     *
     * @return ScopeAccess
     */
    public function getScopeAccess($scope) {
        return $this->scopeAccesses[$scope];
    }

    /**
     * @return ScopeAccess[]
     */
    public function getScopeAccesses() {
        return $this->scopeAccesses;
    }


    /**
     * Generate all object scope accesses for a passed object and scope
     * This will combine any scope accesses specified directly if the object is sharable
     * with any implicit scope access inferred from an object member e.g. accountId
     *
     * @param $scope
     * @return ObjectScopeAccess[]
     */
    public function generateObjectScopeAccesses($object, $scope) {

        $objectScopeAccesses = [];

        // Grab the scope access and resolve the object member
        $scopeAccess = $this->getScopeAccess($scope);
        $objectMember = $scopeAccess->getObjectMember();


        // Ensure that any direct scope values identified by object member are mapped to full
        // Write and grant access.
        $classInspector = $this->classInspectorProvider->getClassInspector(get_class($object));
        if ($objectMember && $classInspector->hasAccessor($objectMember)) {
            $objectScopeAccesses[] = new ObjectScopeAccess($scopeAccess->getScope(), $classInspector->getPropertyData($object, $objectMember), "OWNER", true, true);
        }

        // If we are using the sharable trait, also check for other permissions
        if ($classInspector->usesTrait(Sharable::class)) {
            $objectScopeAccesses = array_merge($objectScopeAccesses, $object->returnValidObjectScopeAccesses($scopeAccess->getScope()));
        }

        return $objectScopeAccesses;
    }


}
