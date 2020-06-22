<?php

namespace Kiniauth\Services\Security\RouteInterceptor;

use Kiniauth\Exception\Security\MissingCSRFHeaderException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\Core\Logging\Logger;
use Kinikit\MVC\Response\JSONResponse;
use Kinikit\MVC\Response\SimpleResponse;
use Kinikit\MVC\Routing\RouteInterceptor;

abstract class WebRouteInterceptor extends RouteInterceptor {

    /**
     * @var SecurityService
     */
    protected $securityService;


    /**
     * Construct with injected dependencies.
     *
     * GlobalRouteInterceptor constructor.
     * @param SecurityService $securityService
     */
    public function __construct($securityService) {
        $this->securityService = $securityService;
    }

    /**
     * Implemnet ths
     *
     * @param \Kinikit\MVC\Request\Request $request
     * @return \Kinikit\MVC\Response\Response|null
     */
    public function beforeRoute($request) {

        list($user, $account) = $this->securityService->getLoggedInUserAndAccount();

        // Shortcut the process if an options request made and return the header allowing the csrf token.
        if (strtolower($request->getRequestMethod()) == "options") {

            $response = new SimpleResponse("");

            // Add the CSRF token as permitted for this route
            $response->setHeader("Access-Control-Allow-Headers", "x-csrf-token");

            return $response;

        } else {

            // Check for CSRF Tokens unless an options query.
            $csrfToken = $request->getHeaders()->getCustomHeader("X_CSRF_TOKEN");
            if (!$csrfToken || $csrfToken != $this->securityService->getCSRFToken()) {
                throw new MissingCSRFHeaderException();
            }

            // Call the custom logic.
            return $this->beforeWebRoute($request, $user, $account);
        }
    }

    /**
     * @param \Kinikit\MVC\Response\Response $response
     * @return \Kinikit\MVC\Response\Response|void
     */
    public function afterRoute($response) {

        // Call the custom logic
        return $this->afterWebRoute($response);

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
     * @param \Kinikit\MVC\Response\Response $response
     * @return \Kinikit\MVC\Response\Response
     */
    public abstract function afterWebRoute($response);


}
