<?php

namespace Kiniauth;


use Kiniauth\Services\Application\BootstrapService;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\MVC\Framework\Dispatcher;

class Init {

    /**
     * Kiniauth initialiser.  This is called from index.php
     */
    public function __construct() {

        // Initialise the app using bootstrap service.
        Container::instance()->get(BootstrapService::class);

        // Call the MVC dispatcher
        (new Dispatcher())->dispatch();

    }

}
