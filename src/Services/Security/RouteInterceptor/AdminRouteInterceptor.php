<?php


namespace Kiniauth\Services\Security\RouteInterceptor;


use Kinikit\Core\Exception\AccessDeniedException;

class AdminRouteInterceptor extends WebRouteInterceptor {

    /**
     * Custom before web logic
     *
     * @param \Kinikit\MVC\Request\Request $request
     * @param $loggedInUser
     * @param $loggedInAccount
     * @return \Kinikit\MVC\Response\Response|void|null
     */
    public function beforeWebRoute($request, $loggedInUser, $loggedInAccount) {

        if (!$this->securityService->isSuperUserLoggedIn())
            throw new AccessDeniedException();

    }

    /**
     * Custom after route logic
     *
     * @param \Kinikit\MVC\Response\Response $response
     * @return \Kinikit\MVC\Response\Response|void
     */
    public function afterWebRoute($response) {
        // TODO: Implement afterWebRoute() method.
    }
}
