<?php

namespace Kiniauth\Services\Security\TwoFactor;


/**
 * Interface TwoFactorProvider
 *
 * @defaultImplementation \Kiniauth\Services\Security\TwoFactor\GoogleAuthenticatorProvider
 *
 * @package Kiniauth\Services\Security\TwoFactor
 */
interface TwoFactorProvider {

    public function createSecretKey();

    public function generateQRCode($secretKey);

    public function authenticate($secretKey, $code);


}
