<?php


namespace Kiniauth\Services\Security;

use Kiniauth\Objects\Security\ObjectScopeAccess;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Workflow\PropertyChangeWorkflow;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Workflow\ObjectWorkflowService;
use Kiniauth\Traits\Application\Timestamped;
use Kiniauth\Traits\Security\Sharable;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Object\SerialisableObject;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;
use Kinikit\Persistence\ORM\Mapping\ORMMapping;
use Kinikit\Persistence\ORM\ORM;
use Kinikit\Persistence\TableMapper\Exception\WrongPrimaryKeyLengthException;
use phpDocumentor\Reflection\Types\Callable_;


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

    /**
     * @var ObjectWorkflowService
     */
    private $objectWorkflowService;

    /**
     * The original object (stored between pre-save and post-save)
     * @var mixed
     */
    private $originalObject;

    private $disabled = false;

    /**
     * @var array
     */
    private $whitelistedReadAccounts = [];


    /**
     * @param SecurityService $securityService
     * @param Session $session
     * @param ClassInspectorProvider $classInspectorProvider
     * @param ORM $orm
     * @param ObjectWorkflowService $objectWorkflowService
     */
    public function __construct($securityService, $session, $classInspectorProvider, $orm, $objectWorkflowService) {
        $this->securityService = $securityService;
        $this->session = $session;
        $this->classInspectorProvider = $classInspectorProvider;
        $this->orm = $orm;
        $this->objectWorkflowService = $objectWorkflowService;
        $this->whitelistedReadAccounts = [];
    }


    public function postMap($object = null, $upfInstance = null) {
        return $this->disabled || $this->resolveAccessForObject($object, false);
    }

    public function preSave($object = null, $upfInstance = null) {

        if (in_array(Timestamped::class, class_uses($object)) || in_array(PropertyChangeWorkflow::class, class_implements($object))) {

            $pk = $this->getPrimaryKeyValues($object);


            try {
                $this->originalObject = $this->orm->fetch(get_class($object), array_values($pk));
            } catch (ObjectNotFoundException|WrongPrimaryKeyLengthException $e) {
                $this->originalObject = null;
            }


            if (in_array(Timestamped::class, class_uses($object))) {

                // Fetch object by pk
                $hasCreatedDate = $this->originalObject?->getCreatedDate();

                $classInspector = $this->classInspectorProvider->getClassInspector(get_class($object));

                $classInspector->setPropertyData($object, $hasCreatedDate ?: new \DateTime(), "createdDate", false);
                $classInspector->setPropertyData($object, new \DateTime(), "lastModifiedDate", false);
            }
        }

        return $this->disabled || $this->resolveAccessForObject($object, true, SecurityService::ACCESS_WRITE);
    }

    public function postSave($object) {

        if (in_array(PropertyChangeWorkflow::class, class_implements($object))) {

            $pk = $this->getPrimaryKeyValues($object);

            if ($pk) {

                $newObject = $this->orm->fetch(get_class($object), array_values($pk));

                // Process property change workflow
                $this->objectWorkflowService->processPropertyChangeWorkflowSteps(get_class($object), array_values($pk)[0], $this->originalObject, $newObject);
            }
        }
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
            $this->disabled = $previousDisabled;
            throw($e);
        }

        $this->disabled = $previousDisabled;

        return $result;
    }


    /**
     * Execute a callable
     *
     * @param Callable $callable
     * @param int $accountId
     *
     */
    public function executeWithWhitelistedAccountReadAccess($callable, $accountId) {

        $previousWhiteListed = $this->whitelistedReadAccounts;

        $this->whitelistedReadAccounts[] = $accountId;

        // Run the callable
        try {
            $result = $callable();
            $this->whitelistedReadAccounts = $previousWhiteListed;
            return $result;
        } catch (\Throwable $e) {
            $this->whitelistedReadAccounts = $previousWhiteListed;
            throw($e);
        }

    }


    /**
     * @param mixed $object
     * @return bool
     */
    private function resolveAccessForObject($object, $throwException = true, $accessMode = SecurityService::ACCESS_READ) {


        if ($this->securityService->checkLoggedInObjectAccess($object, $accessMode))
            return true;

        // If we are attempting to read a sharable object and we have whitelistings, ensure these are encoded
        if (($accessMode == SecurityService::ACCESS_READ) && sizeof($this->whitelistedReadAccounts)) {
            foreach ($this->whitelistedReadAccounts as $accountId) {
                if ($this->securityService->checkObjectScopeAccess($object, Role::SCOPE_ACCOUNT, $accountId, SecurityService::ACCESS_GRANT))
                    return true;
            }
        }


        // If fallen through, throw or return
        if ($throwException)
            throw new AccessDeniedException();
        else
            return false;

    }

    /**
     * @param $object
     * @return array
     */
    private function getPrimaryKeyValues($object) {
        $classInspector = $this->classInspectorProvider->getClassInspector(get_class($object));

        // Grab orm mapping
        $ormMapping = ORMMapping::get(get_class($object));

        $pk = $ormMapping->getReadTableMapping()->getPrimaryKeyValues($classInspector->getPropertyData($object));
        return $pk;
    }


}
