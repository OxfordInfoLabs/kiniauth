<?php

namespace Kiniauth\Services\Security\SSOProvider;

/**
 * @implementation oidc \Kiniauth\Services\Security\SSOProvider\OpenIdAuthenticatorFactory
 * @implementation saml \Kiniauth\Services\Security\SSOProvider\SAMLAuthenticatorFactory
 */
interface AuthenticatorFactory {

    public function create(string $providerKey);

}