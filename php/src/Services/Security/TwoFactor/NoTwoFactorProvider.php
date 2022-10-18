<?php


namespace Kiniauth\Services\Security\TwoFactor;


use Kiniauth\Objects\Security\User;

class NoTwoFactorProvider implements TwoFactorProvider {

    /**
     * Always return false as 2 factor is never required
     *
     * @param User $pendingUser
     * @param mixed $twoFactorClientData
     * @return mixed|void
     */
    public function generateTwoFactorIfRequired($pendingUser, $twoFactorClientData) {
        return false;
    }

    /**
     * No operation required here as it should never attempt to authenticate.
     *
     * @param User $pendingUser
     * @param mixed $pendingTwoFactorData
     * @param mixed $twoFactorLoginData
     * @return mixed|void
     */
    public function authenticate($pendingUser, $pendingTwoFactorData, $twoFactorLoginData) {
    }
}