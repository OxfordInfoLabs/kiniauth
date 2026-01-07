<?php

namespace Kiniauth\Services\Security\SSOProvider;

/**
 * @implementation google \Kiniauth\Services\Security\SSOProvider\GoogleSSOAuthenticator
 * @implementation facebook \Kiniauth\Services\Security\SSOProvider\FacebookSSOAuthenticator
 */
interface SSOAuthenticator {

    /**
     * @param mixed $data
     */
    public function authenticate(mixed $data);

    public function initialise(string $provider);

}