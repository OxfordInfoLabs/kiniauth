<?php


namespace Kiniauth\Exception\Security;


/**
 * Exception raised if an attempt to attach an already attached user to an account.
 *
 * Class UserAlreadyAttachedToAccountException
 * @package Kiniauth\Exception\Security
 */
class UserAlreadyAttachedToAccountException extends \Exception {

    public function __construct($emailAddress) {
        parent::__construct("The user with email address $emailAddress is already attached to this account");
    }

}
