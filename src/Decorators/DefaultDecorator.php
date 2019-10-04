<?php

namespace Kiniauth\Decorators;

use Kinikit\MVC\Response\View;

class DefaultDecorator {

    /**
     * Handle request method
     */
    public function handleRequest() {
        return new View("DefaultDecorator");
    }

}
