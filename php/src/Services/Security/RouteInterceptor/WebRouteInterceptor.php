<?php

namespace Kiniauth\Services\Security\RouteInterceptor;

use Kiniauth\Exception\Security\MissingCSRFHeaderException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\MVC\Request\Request;
use Kinikit\MVC\Request\URL;
use Kinikit\MVC\Response\Headers;
use Kinikit\MVC\Response\SimpleResponse;
use Kinikit\MVC\Routing\RouteInterceptor;

abstract class WebRouteInterceptor extends RouteInterceptor {

    /**
     * @var SecurityService
     */
    protected $securityService;

    /**
     * @var AuthenticationService
     */
    protected $authenticationService;


    /**
     * @var Request
     */
    protected $request;

    /**
     * Flag as to whether or not csrf is enforced - defaults to true
     *
     * @var bool
     */
    protected $csrf = true;


    /**
     * Construct with injected dependencies.
     *
     * @param SecurityService $securityService
     * @param AuthenticationService $authenticationService
     */
    public function __construct($securityService, $authenticationService) {
        $this->securityService = $securityService;
        $this->authenticationService = $authenticationService;
    }

    /**
     * Implement this
     *
     * @param \Kinikit\MVC\Request\Request $request
     * @return \Kinikit\MVC\Response\Response|null
     */
    public function beforeRoute($request) {

        // Handle options requests to allow headers
        if (strtolower($request->getRequestMethod() ?? "") == "options") {
            $response = new SimpleResponse("");
            return $response;
        }

        list($user, $account) = $this->securityService->getLoggedInSecurableAndAccount();

        // Authenticate using referrer / origin to ensure we are allowed in.
        $this->authenticationService->updateActiveParentAccount($this->getReferrer($request));


        // If enforcing csrf do the main job
        if ($this->csrf) {

            // Check for CSRF Tokens unless an options query.
            $csrfToken = $request->getHeaders()->getCustomHeader("X_CSRF_TOKEN");
            if (!$csrfToken || $csrfToken != $this->securityService->getCSRFToken()) {
                throw new MissingCSRFHeaderException();
            }

            // Call the custom logic.
            return $this->beforeWebRoute($request, $user, $account);

        } else {

            return $this->beforeWebRoute($request, $user, $account);
        }
    }

    /**
     * @param \Kinikit\MVC\Request\Request $request
     * @param \Kinikit\MVC\Response\Response $response
     *
     * @return \Kinikit\MVC\Response\Response|void
     */
    public function afterRoute($request, $response) {

        $referrer = $this->getReferrer($request);

        // Check we have an active referrer - if so we can assume that the request referrer is valid.
        if ((strtolower($request->getRequestMethod() ?? "") == "options") || ($this->authenticationService->hasActiveReferrer() && $referrer)) {

            // Allow the origin via a header
            $accessControlOrigin = strtolower($referrer->getProtocol()) . "://" . $referrer->getHost() . ($referrer->getPort() != "80" && $referrer->getPort() != "443" ? ":" . $referrer->getPort() : "");
            $response->setHeader(Headers::HEADER_ACCESS_CONTROL_ALLOW_ORIGIN, $accessControlOrigin);

            // Allow credentials via a header
            $response->setHeader(Headers::HEADER_ACCESS_CONTROL_ALLOW_CREDENTIALS, "true");

            // Allow content type
            $response->setHeader(Headers::HEADER_ACCESS_CONTROL_ALLOW_HEADERS, "content-type");

            // Add the capcha token as permitted in all cases
            $response->setHeader(Headers::HEADER_ACCESS_CONTROL_ALLOW_HEADERS, "x-captcha-token");

            // Add the CSRF token as permitted for this route
            $response->setHeader(Headers::HEADER_ACCESS_CONTROL_ALLOW_HEADERS, "x-csrf-token");

            // Allow methods
            $response->setHeader(Headers::HEADER_ACCESS_CONTROL_ALLOW_METHODS, "GET,POST,DELETE,PUT,PATCH,OPTIONS");

        }

        // Call the custom logic
        return $this->afterWebRoute($request, $response);

    }


    /**
     * Custom before web logic
     *
     * @param \Kinikit\MVC\Request\Request $request
     * @param User $loggedInUser
     * @param Account $loggedInAccount
     * @return \Kinikit\MVC\Response\Response|null
     */
    public abstract function beforeWebRoute($request, $loggedInUser, $loggedInAccount);


    /**
     * Custom after route logic
     *
     * @param \Kinikit\MVC\Request\Request $request
     * @param \Kinikit\MVC\Response\Response $response
     * @return \Kinikit\MVC\Response\Response
     */
    public abstract function afterWebRoute($request, $response);


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
