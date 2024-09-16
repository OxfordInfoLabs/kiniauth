<?php


namespace Kiniauth\Services\Security\RouteInterceptor;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;

/**
 * Dummy guest route interceptor to allow for web route core logic to run with csrf disabled.
 *
 * Class GuestRouteInterceptor
 * @package Kiniauth\Services\Security\RouteInterceptor
 */
class GuestRouteInterceptor extends WebRouteInterceptor {

    const WHITELISTED_ENDPOINTS = [
        "guest/session",
        "guest/auth/logout"
    ];

    /**
     * @param SecurityService $securityService
     * @param AuthenticationService $authenticationService
     *
     */
    public function __construct($securityService, $authenticationService) {
        parent::__construct($securityService, $authenticationService);
    }

    public function beforeRoute($request) {

        // Enforce CSRF unless in whitelisted endpoints array
        $routePath = $request->getUrl()->getPath();
        $this->csrf = !in_array($routePath, self::WHITELISTED_ENDPOINTS);


        return parent::beforeRoute($request);
    }


    /**
     * Custom before web logic
     *
     * @param \Kinikit\MVC\Request\Request $request
     * @param User $loggedInUser
     * @param Account $loggedInAccount
     * @return \Kinikit\MVC\Response\Response|null
     */
    public function beforeWebRoute($request, $loggedInUser, $loggedInAccount) {
    }

    /**
     * Custom after route logic
     *
     * @param \Kinikit\MVC\Response\Response $response
     * @return \Kinikit\MVC\Response\Response
     */
    public function afterWebRoute($request, $response) {
        return $response;
    }
}
