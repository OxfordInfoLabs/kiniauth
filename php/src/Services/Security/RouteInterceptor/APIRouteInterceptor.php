<?php


namespace Kiniauth\Services\Security\RouteInterceptor;


use Kiniauth\Exception\Security\MissingAPICredentialsException;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\MVC\Routing\RouteInterceptor;

class APIRouteInterceptor extends RouteInterceptor {

    /**
     * @var SecurityService
     */
    private $securityService;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * APIRouteInterceptor constructor.
     * @param SecurityService $securityService
     * @param AuthenticationService $authenticationService
     */
    public function __construct($securityService, $authenticationService) {
        $this->securityService = $securityService;
        $this->authenticationService = $authenticationService;
    }

    /**
     * API Before route
     *
     * @param \Kinikit\MVC\Request\Request $request
     * @return \Kinikit\MVC\Response\Response|void|null
     */
    public function beforeRoute($request) {

        $apiKey = $request->getParameter("apiKey") ?? $request->getHeaders()->getCustomHeader("API_KEY");
        $apiSecret = $request->getParameter("apiSecret") ?? $request->getHeaders()->getCustomHeader("API_SECRET");
        if (!$apiKey || !$apiSecret) {
            throw new MissingAPICredentialsException();
        }

        list($securable, $account) = $this->securityService->getLoggedInSecurableAndAccount();

        if (!$securable || $securable instanceof User || $securable->getApiKey() != $apiKey || $securable->getApiSecret() != $apiSecret) {
            $this->authenticationService->apiAuthenticate($apiKey, $apiSecret);
        }

    }


}
