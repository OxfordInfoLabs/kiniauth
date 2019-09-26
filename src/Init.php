<?php

namespace Kiniauth;


use Kiniauth\Controllers\Guest\Auth;
use Kiniauth\Services\Application\BootstrapService;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\MVC\Routing\Router;

class Init {

    /**
     * Kiniauth initialiser.  This is called from index.php
     */
    public function __construct() {

        // Initialise the app using bootstrap service.
        Container::instance()->get(BootstrapService::class);
        
        // Invoke the router
        Router::route();

    }

}
