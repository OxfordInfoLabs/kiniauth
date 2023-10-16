<?php

namespace Kiniauth\Services\Security\RouteInterceptor;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Security\Hash\SHA512HashProvider;
use Kinikit\MVC\Request\Request;
use Kinikit\MVC\Routing\RouteInterceptor;
use Kinikit\MVC\Routing\RouteNotFoundException;

class InternalRouteInterceptor extends RouteInterceptor {

    /**
     * @var SHA512HashProvider
     */
    private $hashProvider;


    /**
     * @param SHA512HashProvider $hashProvider
     */
    public function __construct($hashProvider) {
        $this->hashProvider = $hashProvider;
    }


    /**
     * Intercept route requests for internal and ensure we have the authentication hash in place
     *
     * @param Request $request
     * @return void
     */
    public function beforeRoute($request) {

        // grab auth hash
        $authHash = $request->getHeaders()->getCustomHeader("AUTH_HASH");

        // If no auth hash
        if (!$authHash)
            throw new RouteNotFoundException($request->getUrl()->getPath());

        // Construct comparison hash
        $expectedHash = $this->hashProvider->generateHash(Configuration::readParameter("internal.controller.secret"));

        // If none matching hash throw
        if ($authHash != $expectedHash)
            throw new RouteNotFoundException($request->getUrl()->getPath());
    }


}