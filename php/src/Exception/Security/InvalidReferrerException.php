<?php


namespace Kiniauth\Exception\Security;


class InvalidReferrerException extends \Exception {

    public function __construct() {
        parent::__construct("Invalid referrer supplied for webservice");
    }

}

