<?php


namespace Kiniauth\Services\Security;

use Kiniauth\Objects\Application\Session;
use Kiniauth\Traits\Application\Timestamped;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Object\SerialisableObject;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;
use Kinikit\Persistence\ORM\Mapping\ORMMapping;
use Kinikit\Persistence\ORM\ORM;
use Kinikit\Persistence\TableMapper\Exception\WrongPrimaryKeyLengthException;


/**
 * Generic object interceptor for intercepting requests for all objects.  This predominently enforces security rules
 * around objects containing an accountId property.
 *
 * @noProxy
 */
class ActiveRecordInterceptor extends DefaultORMInterceptor {

    private $securityService;
    private $session;

    /**
     * @var ClassInspectorProvider
     */
    private $classInspectorProvider;

    /**
     * @var ORM
     */
    private $orm;

    private $disabled = false;


    /**
     * @param \Kiniauth\Services\Security\SecurityService $securityService
     * @param \Kiniauth\Services\Application\Session $session
     * @param ClassInspectorProvider $classInspectorProvider
     * @param ORM $orm
     */
    public function __construct($securityService, $session, $classInspectorProvider, $orm) {
        $this->securityService = $securityService;
        $this->session = $session;
        $this->classInspectorProvider = $classInspectorProvider;
        $this->orm = $orm;
    }


    public function postMap($object = null, $upfInstance = null) {
        return $this->disabled || $this->resolveAccessForObject($object, false);
    }

    public function preSave($object = null, $upfInstance = null) {

        if (in_array(Timestamped::class, class_uses($object))) {
            $classInspector = $this->classInspectorProvider->getClassInspector(get_class($object));

            // Grab orm mapping
            $ormMapping = ORMMapping::get(get_class($object));

            // Get the PK from table mapping
            $pk = $ormMapping->getReadTableMapping()->getPrimaryKeyValues($classInspector->getPropertyData($object));

            // Fetch object by pk
            $hasCreatedDate = false;

            try {
                $existingObject = $this->orm->fetch(get_class($object), array_values($pk));
                $hasCreatedDate = $existingObject->getCreatedDate();
            } catch (ObjectNotFoundException|WrongPrimaryKeyLengthException $e) {
            }


            $classInspector->setPropertyData($object, $hasCreatedDate ?: new \DateTime(), "createdDate", false);
            $classInspector->setPropertyData($object, new \DateTime(), "lastModifiedDate", false);
        }

        return $this->disabled || $this->resolveAccessForObject($object, true, SecurityService::ACCESS_WRITE);
    }

    public function preDelete($object = null, $upfInstance = null) {
        return $this->disabled || $this->resolveAccessForObject($object, true, SecurityService::ACCESS_WRITE);
    }


    /**
     * Execute a callable block insecurely with interceptors disabled.
     *
     * @param callable $callable
     */
    public function executeInsecure($callable) {

        $previousDisabled = $this->disabled;

        // Disable for the duration of the callable
        $this->disabled = true;

        // Run the callable
        try {
            $result = $callable();
        } catch (\Throwable $e) {
            $this->disabled = false;
            throw($e);
        }

        $this->disabled = $previousDisabled;

        return $result;
    }


    /**
     * @param mixed $object
     * @return bool
     */
    private function resolveAccessForObject($object, $throwException = true, $accessMode = SecurityService::ACCESS_READ) {


        if ($this->securityService->checkLoggedInObjectAccess($object, $accessMode))
            return true;
        else {
            if ($throwException)
                throw new AccessDeniedException();
            else
                return false;
        }
    }


}
