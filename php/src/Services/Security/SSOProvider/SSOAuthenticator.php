<?php

namespace Kiniauth\Services\Security\SSOProvider;

/**
 * @implementation google \Kiniauth\Services\Security\Services\SSOProvider\GoogleSSOAuthenticator
 * @implementation facebook \Kiniauth\Services\Security\Services\SSOProvider\FacebookSSOAuthenticator
 */
interface SSOAuthenticator {

    /**
     * @param mixed $data
     */
    public function authenticate(mixed $data);

}