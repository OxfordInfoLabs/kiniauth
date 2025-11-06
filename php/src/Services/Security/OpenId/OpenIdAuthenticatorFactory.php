<?php

namespace Kiniauth\Services\Security\OpenId;

use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\JWT\JWTManager;
use Kiniauth\ValueObjects\Security\SSO\OpenIdAuthenticatorConfiguration;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;

class OpenIdAuthenticatorFactory {

    /**
     * Creates and returns a fully configured OpenIdAuthenticator instance.
     *
     * @param string $provider The identifier for the SSO provider
     * @return OpenIdAuthenticator
     */
    public function create(string $provider): OpenIdAuthenticator {

        $requestDispatcher = Container::instance()->get(HttpRequestDispatcher::class);
        $session = Container::instance()->get(Session::class);

        $config = new OpenIdAuthenticatorConfiguration($provider);

        $jwtSecret = Configuration::readParameter("sso.$provider.jwt.secret");
        $jwtAlg = Configuration::readParameter("sso.$provider.jwt.alg");

        $jwtManager = new JWTManager($jwtAlg, $jwtSecret);

        $authenticator = new OpenIdAuthenticator(
            $requestDispatcher,
            $session,
            $config,
            $jwtManager
        );

        return $authenticator;
    }
}