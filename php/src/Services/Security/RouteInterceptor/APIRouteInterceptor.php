<?php


namespace Kiniauth\Services\Security\RouteInterceptor;


use Kiniauth\Exception\Security\MissingAPICredentialsException;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\Core\Logging\Logger;
use Kinikit\MVC\Request\URL;
use Kinikit\MVC\Response\Headers;
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

        $this->authenticationService->apiAuthenticate($apiKey, $apiSecret);
    }


    /**
     * Allow referrer based API calls
     *
     * @param \Kinikit\MVC\Request\Request $request
     * @param \Kinikit\MVC\Response\Response $response
     * @return \Kinikit\MVC\Response\Response
     */
    public function afterRoute($request, $response) {
        $referrer = $this->getReferrer($request);

        // Check we have an active referrer - if so we can assume that the request referrer is valid.
        if ($this->authenticationService->hasActiveReferrer() && $referrer) {
            $accessControlOrigin = strtolower($referrer->getProtocol()) . "://" . $referrer->getHost() . ($referrer->getPort() != "80" && $referrer->getPort() != "443" ? ":" . $referrer->getPort() : "");
            $response->setHeader(Headers::HEADER_ACCESS_CONTROL_ALLOW_CREDENTIALS, "true");
            $response->setHeader(Headers::HEADER_ACCESS_CONTROL_ALLOW_ORIGIN, $accessControlOrigin);
        }

        return $response;
    }


    // Get normalised referrer
    private function getReferrer($request) {
        $referrer = null;
        if ($request->getReferringURL()) {
            $referrer = $request->getReferringURL();
        } else if ($request->getHeaders()->getCustomHeader("ORIGIN")) {
            $referrer = new URL($request->getHeaders()->getCustomHeader("ORIGIN"));
        }
        return $referrer;
    }

}
