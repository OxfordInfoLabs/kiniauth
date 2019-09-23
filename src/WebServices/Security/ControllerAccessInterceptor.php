<?php


namespace Kiniauth\WebServices\Security;

use Kinikit\Core\DependencyInjection\ObjectInterceptor;
use Kinikit\Core\Util\HTTP\URLHelper;
use Kinikit\MVC\Framework\Controller;
use Kinikit\MVC\Framework\Controller\RESTService;


/**
 * Control access to controllers within the system.
 *
 * @package Kiniauth\WebServices\Security
 */
abstract class ControllerAccessInterceptor extends ObjectInterceptor {


    /**
     * Before method interceptor for controller access.
     *
     * @param object $objectInstance
     * @param string $methodName
     * @param $params
     * @param \Kinikit\Core\Util\Annotation\ClassAnnotations $classAnnotations
     * @return string
     */
    public function afterCreate($objectInstance) {
        if ($objectInstance instanceof Controller) {
            $this->onControllerAccess($objectInstance, new URLHelper($_SERVER ['REQUEST_URI']));
        }

    }


    /**
     * On controller access method, called from above.
     *
     * @param Controller $controllerInstance
     * @param $methodName
     * @param URLHelper $urlHelper
     */
    public abstract function onControllerAccess($controllerInstance, $urlHelper);


}
