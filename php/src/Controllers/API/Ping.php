<?php


namespace Kiniauth\Controllers\API;


use Kinikit\MVC\Response\SimpleResponse;

class Ping {

    /**
     * Ping constructor.
     */
    public function handleRequest() {
        return new SimpleResponse("Success");
    }
}