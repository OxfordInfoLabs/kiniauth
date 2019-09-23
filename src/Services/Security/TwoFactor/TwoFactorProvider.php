<?php

namespace Kiniauth\Services\Security\TwoFactor;

abstract class TwoFactorProvider {

    const PROVIDER_GOOGLE_AUTHENTICATOR = "GOOGLE_AUTHENTICATOR";

    public abstract function createSecretKey();

    public abstract function generateQRCode($secretKey);

    public abstract function authenticate($secretKey, $code);

    /**
     * Get the active transport in use
     */
    public static function getProvider($providerKey) {

        switch ($providerKey) {
            case self::PROVIDER_GOOGLE_AUTHENTICATOR:
                return new GoogleAuthenticatorProvider();
                break;
            default:
                return new GoogleAuthenticatorProvider();

        }

    }

}
