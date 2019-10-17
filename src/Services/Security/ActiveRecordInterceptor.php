<?php


namespace Kiniauth\Services\Security;

use Kiniauth\Objects\Application\Session;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Object\SerialisableObject;
use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;


/**
 * Generic object interceptor for intercepting requests for all objects.  This predominently enforces security rules
 * around objects containing an accountId property.
 *
 * @noProxy
 */
class ActiveRecordInterceptor extends DefaultORMInterceptor {

    private $securityService;
    private $session;

    private $disabled = false;


    /**
     * @param \Kiniauth\Services\Security\SecurityService $securityService
     * @param \Kiniauth\Services\Application\Session $session
     */
    public function __construct($securityService, $session) {
        $this->securityService = $securityService;
        $this->session = $session;
    }


    public function postMap($object = null, $upfInstance = null) {
        return $this->disabled || $this->resolveAccessForObject($object, false);
    }

    public function preSave($object = null, $upfInstance = null) {
        return $this->disabled || $this->resolveAccessForObject($object);
    }

    public function preDelete($object = null, $upfInstance = null) {
        return $this->disabled || $this->resolveAccessForObject($object);
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
    private function resolveAccessForObject($object, $throwException = true) {
        if ($this->securityService->checkLoggedInObjectAccess($object))
            return true;
        else {
            if ($throwException)
                throw new AccessDeniedException();
            else
                return false;
        }
    }


}
