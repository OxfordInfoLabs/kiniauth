<?php

namespace Kiniauth\Services\Security\SSOProvider;

abstract class SSOAuthenticator {

    /**
     * @param mixed $data
     */
    public abstract function authenticate(mixed $data);

}