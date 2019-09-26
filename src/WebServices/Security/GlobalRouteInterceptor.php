<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 26/09/2019
 * Time: 12:07
 */

namespace Kiniauth\WebServices\Security;


use Kiniauth\Exception\Security\MissingAPICredentialsException;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\MVC\Routing\RouteInterceptor;


/**
 *
 * @noProxy
 * 
 * Class GlobalRouteInterceptor
 * @package Kiniauth\WebServices\Security
 */
class GlobalRouteInterceptor extends RouteInterceptor {

    /**
     * @var SecurityService
     */
    private $securityService;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * Construct with injected dependencies.
     *
     * GlobalRouteInterceptor constructor.
     * @param SecurityService $securityService
     * @param AuthenticationService $authenticationService
     */
    public function __construct($securityService, $authenticationService) {
        $this->securityService = $securityService;
        $this->authenticationService = $authenticationService;
    }


    /**
     * Intercept all controller requests
     *
     * @param \Kinikit\MVC\Request\Request $request
     */
    public function beforeRoute($request) {

        $controlSegment = $request->getUrl()->getFirstPathSegment();
        
        list($user, $account) = $this->securityService->getLoggedInUserAndAccount();

        // If customer segment, make sure at least someone is logged in.
        if ($controlSegment == "customer") {
            if (!$user && !$account)
                throw new AccessDeniedException();
        } else if ($controlSegment == "admin") {
            if (!$this->securityService->isSuperUserLoggedIn())
                throw new AccessDeniedException();
        } else if ($controlSegment == "api") {

            $apiKey = $request->getParameter("apiKey");
            $apiSecret = $request->getParameter("apiSecret");
            if (!$apiKey || !$apiSecret) {
                throw new MissingAPICredentialsException();
            }
            if (!$account || $account->getApiKey() != $apiKey || $account->getApiSecret() != $apiSecret) {
                $this->authenticationService->apiAuthenticate($apiKey, $apiSecret);
            }
        }

    }


}