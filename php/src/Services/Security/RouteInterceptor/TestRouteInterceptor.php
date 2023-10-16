<?php

namespace Kiniauth\Services\Security\RouteInterceptor;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\MVC\Routing\RouteInterceptor;
use Kinikit\MVC\Routing\RouteNotFoundException;

class TestRouteInterceptor extends RouteInterceptor {

    /**
     * Confirm that the configuration parameter is set before proceeding
     *
     * @param $request
     * @return void
     */
    public function beforeRoute($request) {
        if (!Configuration::readParameter("test.routes.enabled"))
            throw new RouteNotFoundException($request->getUrl()->getPath());
    }


}