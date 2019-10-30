<?php


namespace Kiniauth\Exception\Security;


use Kinikit\Core\Exception\AccessDeniedException;

/**
 * Two factor authentication required exception.  Used when creating user access tokens
 * where a single login attempt is required.
 *
 * Class TwoFactorAuthenticationRequiredException
 * @package Kiniauth\Exception\Security
 */
class TwoFactorAuthenticationRequiredException extends AccessDeniedException {

    public function __construct() {
        parent::__construct("You must also supply a two factor authentication code");
    }

}
