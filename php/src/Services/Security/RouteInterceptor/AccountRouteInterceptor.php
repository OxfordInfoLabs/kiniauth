<?php


namespace Kiniauth\Services\Security\RouteInterceptor;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\User;
use Kinikit\Core\Exception\AccessDeniedException;

class AccountRouteInterceptor extends WebRouteInterceptor {

    /**
     * Custom before web logic
     *
     * @param \Kinikit\MVC\Request\Request $request
     * @param User $loggedInUser
     * @param Account $loggedInAccount
     * @return \Kinikit\MVC\Response\Response
     */
    public function beforeWebRoute($request, $loggedInUser, $loggedInAccount) {

        if (!$loggedInUser instanceof User)
            throw new AccessDeniedException();


    }

    /**
     * Custom after route logic
     *
     * @param \Kinikit\MVC\Response\Response $response
     * @return \Kinikit\MVC\Response\Response|void
     */
    public function afterWebRoute($request, $response) {
        return $response;
    }
}
