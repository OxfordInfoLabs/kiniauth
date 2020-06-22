<?php


namespace Kiniauth\Services\Security\RouteInterceptor;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\User;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Logging\Logger;

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

        if (!$loggedInUser)
            throw new AccessDeniedException();


    }

    /**
     * Custom after route logic
     *
     * @param \Kinikit\MVC\Response\Response $response
     * @return \Kinikit\MVC\Response\Response|void
     */
    public function afterWebRoute($response) {
        return $response;
    }
}
