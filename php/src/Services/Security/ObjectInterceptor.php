<?php


namespace Kiniauth\Services\Security;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\Captcha\CaptchaProvider;
use Kinikit\Core\DependencyInjection\ContainerInterceptor;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Reflection\Method;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\MVC\Request\Request;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\ORM\ORM;

/**
 * Generic method interceptor.  Currently allows for privilege based enforcement at the method level as well
 * as an ability to override object interceptors using markup.
 */
class ObjectInterceptor extends ContainerInterceptor {

    private $objectInterceptor;
    private $securityService;


    /**
     * @var CaptchaProvider
     */
    private $captchaProvider;

    /**
     * @var Request
     */
    private $request;


    /**
     * @var Session
     */
    private $session;

    /**
     * @var ORM
     */
    private $orm;


    /**
     * @param \Kiniauth\Services\Security\ActiveRecordInterceptor $objectInterceptor
     * @param \Kiniauth\Services\Security\SecurityService $securityService
     * @param CaptchaProvider
     * @param Request
     * @param Session
     * @param ORM
     */
    public function __construct($objectInterceptor, $securityService, $captchaProvider, $request, $session, $orm) {
        $this->objectInterceptor = $objectInterceptor;
        $this->securityService = $securityService;
        $this->captchaProvider = $captchaProvider;
        $this->request = $request;
        $this->session = $session;
        $this->orm = $orm;
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

        // Resolve any referenceParams
        if ($referenceParams = $methodInspector->getMethodAnnotations()["referenceParameter"] ?? []) {
            foreach ($referenceParams as $referenceParam) {

                // Split the value up into two parts for param name and lookup data.
                $splitValue = preg_split("/ +/", $referenceParam->getValue());

                // Grab lookup data
                preg_match("/^(.*?)\\(\\$(.*?)\\)$/", $splitValue[1], $pkLookupData);


                if (sizeof($pkLookupData) == 3) {
                    // Grab the PK Value
                    $pkValue = ObjectArrayUtils::getObjectMemberValue($params, $pkLookupData[2]);

                    // Resolve the class using installed namespaces
                    $lookupClassName = $methodInspector->getDeclaringClassInspector()->getDeclaredNamespaceClasses()[$pkLookupData[1]] ?? $pkLookupData[1];

                    // Attempt PK lookup
                    try {
                        $params[trim($splitValue[0], " $")] = $this->orm->fetch($lookupClassName, $pkValue);
                    } catch (ObjectNotFoundException $e) {
                        throw new AccessDeniedException();
                    }

                }
            }
        }

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
                    $scopeId = ObjectArrayUtils::getObjectMemberValue($params, $paramName);


                } else {
                    $privilegeKey = trim($matchValue);
                    $scopeId = null;
                }

                $explodedPrivKey = explode(":", $privilegeKey);
                $privilegeScope = null;
                if (sizeof($explodedPrivKey) == 1) {
                    $privilegeScope = Role::SCOPE_ACCOUNT;
                } else {
                    $privilegeScope = $explodedPrivKey[0];
                    $privilegeKey = $explodedPrivKey[1];
                }


                // Throw if an issue is encountered.
                if (!$this->securityService->checkLoggedInHasPrivilege($privilegeScope, $privilegeKey, $scopeId))
                    throw new AccessDeniedException();


            }
        }


        if ($key = array_search(Account::LOGGED_IN_ACCOUNT, $params, true)) {
            list($user, $account) = $this->securityService->getLoggedInSecurableAndAccount();
            if ($account) {
                $params[$key] = $account->getAccountId();
            } else {
                $params[$key] = null;
            }
        }

        if ($key = array_search(User::LOGGED_IN_USER, $params, true)) {
            list($user, $account) = $this->securityService->getLoggedInSecurableAndAccount();
            if ($user instanceof User) {
                $params[$key] = $user->getId();
            } else {
                $params[$key] = null;
            }
        }


        // Check for captchas
        $captchas = $methodInspector->getMethodAnnotations()["captcha"] ?? null;
        if ($captchas) {
            $requiresCaptcha = false;
            if ($captchas[0]->getValue()) {
                $toleratedFailures = trim($captchas[0]->getValue());

                // Grab the method path
                $methodPath = $this->request->getUrl()->getPath();

                $failures = $this->session->__getDelayedCaptcha($methodPath);
                $failures++;
                $this->session->__addDelayedCaptcha($methodPath, $failures);

                $requiresCaptcha = ($failures > $toleratedFailures);

            } else {
                $requiresCaptcha = true;
            }

            // If we require a captcha - check we have one as a header
            if ($requiresCaptcha) {
                $captchaData = $this->request->getHeaders()->getCustomHeader("X_CAPTCHA_TOKEN");
                if ($captchaData) {
                    $captchaSuccess = $this->captchaProvider->verifyCaptcha($captchaData, $this->request);
                    if (!$captchaSuccess) {
                        throw new AccessDeniedException("Invalid captcha supplied");
                    }
                } else {
                    throw new AccessDeniedException("Captcha required but not supplied");
                }
            }

        }


        return $params;


    }

    /**
     * After method
     *
     * @param $objectInstance
     * @param $methodName
     * @param $params
     * @param $returnValue
     * @param Method $methodInspector
     * @return mixed|void
     */
    public function afterMethod($objectInstance, $methodName, $params, $returnValue, $methodInspector) {

        $captchas = $methodInspector->getMethodAnnotations()["captcha"] ?? null;
        if ($captchas && $captchas[0]->getValue()) {
            $methodPath = $this->request->getUrl()->getPath();
            $this->session->__removeDelayedCaptcha($methodPath);
        }

        return $returnValue;
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
