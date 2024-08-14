<?php


namespace Kiniauth\Services\Security\RouteInterceptor;


use Kiniauth\Exception\Security\MissingAPICredentialsException;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\MVC\Request\URL;
use Kinikit\MVC\Response\Headers;
use Kinikit\MVC\Response\SimpleResponse;
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

        $referrer = $this->getReferrer($request);

        // If a referrer is detected do additional web browser security checks
        if ($referrer) {

            // Authenticate using referrer / origin to ensure we are allowed in.
            $this->authenticationService->updateActiveParentAccount($referrer);

            // Handle options requests to allow headers
            if (strtolower($request->getRequestMethod()) == "options") {
                $response = new SimpleResponse("");

                // Allow content type
                $response->setHeader(Headers::HEADER_ACCESS_CONTROL_ALLOW_HEADERS, "content-type");

                // Add the capcha token as permitted in all cases
                $response->setHeader(Headers::HEADER_ACCESS_CONTROL_ALLOW_HEADERS, "x-captcha-token");

                // Add the CSRF token as permitted for this route
                $response->setHeader(Headers::HEADER_ACCESS_CONTROL_ALLOW_HEADERS, "x-csrf-token");

                // Allow methods
                $response->setHeader(Headers::HEADER_ACCESS_CONTROL_ALLOW_METHODS, "GET,POST,DELETE,PUT,PATCH,OPTIONS");

                return $response;
            }
        }


        $apiKey = $request->getParameter("apiKey") ?? $request->getHeaders()->getCustomHeader("API_KEY");
        $apiSecret = $request->getParameter("apiSecret") ?? $request->getHeaders()->getCustomHeader("API_SECRET");
        if (!$apiKey || !$apiSecret) {
            throw new MissingAPICredentialsException();
        }

        $this->authenticationService->apiAuthenticate($apiKey, $apiSecret, $request);
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

        // Remove cookies
        $response->getHeaders()->remove(Headers::HEADER_SET_COOKIE);

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
