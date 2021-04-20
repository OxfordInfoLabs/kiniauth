<?php


namespace Kiniauth\Services\Security\RouteInterceptor;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\Core\Logging\Logger;

/**
 * Dummy guest route interceptor to allow for web route core logic to run with csrf disabled.
 *
 * Class GuestRouteInterceptor
 * @package Kiniauth\Services\Security\RouteInterceptor
 */
class GuestRouteInterceptor extends WebRouteInterceptor {

    /**
     * @param SecurityService $securityService
     * @param AuthenticationService $authenticationService
     *
     */
    public function __construct($securityService, $authenticationService) {
        parent::__construct($securityService, $authenticationService);
        $this->csrf = false;
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
