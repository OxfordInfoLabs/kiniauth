<?php

namespace Kiniauth\Services\Security\TwoFactor;


use Kiniauth\Objects\Security\User;

/**
 * Interface TwoFactorProvider
 *
 * @implementationConfigParam twofactor.provider
 * @defaultImplementation \Kiniauth\Services\Security\TwoFactor\NoTwoFactorProvider
 * @implementation email \Kiniauth\Services\Security\TwoFactor\EmailConfirmationTwoFactorProvider
 * @implementation google-authenticator \Kiniauth\Services\Security\TwoFactor\GoogleAuthenticatorProvider
 *
 *
 * @package Kiniauth\Services\Security\TwoFactor
 */
interface TwoFactorProvider {

    /**
     * Generate a two factor workflow if required.
     * This is called as part of the initial username/password phase and
     * accepts any client data if relevant (can be null).  This should
     * return any pending two factor data required for authentication
     * or false if no two factor is required
     *
     * @param User $pendingUser
     * @param mixed $twoFactorClientData
     *
     * @return mixed
     */
    public function generateTwoFactorIfRequired($pendingUser, $twoFactorClientData);

    /**
     * Authenticate based upon the pending user, any pending two factor data returned from the generate
     * function and two factor login data which is typically a code or similar from the client.
     * This should return either false if the authentication failed or a none false value which will be returned
     * to the client if authentication was successful.

     *
     * @param User $pendingUser
     * @param mixed $pendingTwoFactorData
     * @param mixed $twoFactorLoginData
     *
     * @return mixed
     */
    public function authenticate($pendingUser, $pendingTwoFactorData, $twoFactorLoginData);


}
