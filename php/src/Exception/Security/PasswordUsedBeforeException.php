<?php


namespace Kiniauth\Exception\Security;


class PasswordUsedBeforeException extends \Exception {

    public function __construct() {
        parent::__construct("The password supplied has been used before, please choose another one");
    }

}