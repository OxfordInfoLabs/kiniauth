<?php


namespace Kiniauth\Services\Security;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Application\Session;
use Kinikit\Core\DependencyInjection\ContainerInterceptor;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Reflection\Method;

/**
 * Generic method interceptor.  Currently allows for privilege based enforcement at the method level as well
 * as an ability to override object interceptors using markup.
 */
class ObjectInterceptor extends ContainerInterceptor {

    private $objectInterceptor;
    private $securityService;


    /**
     * @param \Kiniauth\Services\Security\ActiveRecordInterceptor $objectInterceptor
     * @param \Kiniauth\Services\Security\SecurityService $securityService
     */
    public function __construct($objectInterceptor, $securityService) {
        $this->objectInterceptor = $objectInterceptor;
        $this->securityService = $securityService;
    }


    /**
     * Check for privileges before we allow the method to be executed.
     * Also, allow for plugging in of logged in data as default data if required.
     *
     * @param object $objectInstance - The object being called
     * @param string $methodName - The method name
     * @param string [string] $params - The parameters passed to the method as name => value pairs.
     * @param Method $methodInspector - The method inspector
     * for this class method.
     *
     * @return string[string] - The params array either intact or modified if required.
     */
    public function beforeMethod($objectInstance, $methodName, $params, $methodInspector) {

        if ($matches =
            $methodInspector->getMethodAnnotations()["hasPrivilege"] ?? []
        ) {

            foreach ($matches as $match) {

                // Work out which scenario we are in - implicit account or explicit parameter.
                $matchValue = $match->getValue();
                preg_match("/(.+)\((.+)\)/", $matchValue, $matches);

                if ($matches && sizeof($matches) == 3) {

                    $privilegeKey = trim($matches[1]);
                    $paramName = ltrim($matches[2], "$");

                    // Locate the parameter in the method signature
                    $scopeId = isset($params[$paramName]) ? $params[$paramName] : null;

                } else {
                    $privilegeKey = trim($matchValue);
                    $scopeId = null;
                }

                // Throw if an issue is encountered.
                if (!$this->securityService->checkLoggedInHasPrivilege($privilegeKey, $scopeId))
                    throw new AccessDeniedException();


            }
        }
        
        if ($key = array_search(Account::LOGGED_IN_ACCOUNT, $params)) {
            list($user, $account) = $this->securityService->getLoggedInUserAndAccount();
            if ($account) {
                $params[$key] = $account->getAccountId();
            } else {
                $params[$key] = null;
            }
        }

        if ($key = array_search(User::LOGGED_IN_USER, $params)) {
            list($user, $account) = $this->securityService->getLoggedInUserAndAccount();
            if ($user) {
                $params[$key] = $user->getId();
            } else {
                $params[$key] = null;
            }
        }

        return $params;


    }


    /**
     * Check for object interceptor disabling.
     *
     * @param callable $callable
     * @param Method $methodInspector
     *
     * @return callable
     */
    public function methodCallable($callable, $methodName, $params, $methodInspector) {

        // Check for objectInterceptorDisabled
        if ($methodInspector->getMethodAnnotations()["objectInterceptorDisabled"] ?? []) {
            return function () use ($callable) {
                return $this->objectInterceptor->executeInsecure($callable);
            };
        }

        return $callable;
    }


}
