<?php

namespace Kiniauth\Services\Security\SSOProvider;

use Kiniauth\Objects\Account\Account;
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

        $config = $this->getConfiguration($provider);

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

    private function getConfiguration($provider) {

        /**
         * Lookup the OpenID settings for the account based on supplied provider.
         * @var Account[] $accounts
         */
        $accounts = Account::filter("WHERE settings LIKE ?", "%\"provider\":\"$provider\"%");
        if (sizeof($accounts) > 0) {
            $accountSettings = $accounts[0]->getSettings();
            $openIdSettings = $accountSettings["openId"];
        } else {
            return null;
        }

        return new OpenIdAuthenticatorConfiguration(
            $openIdSettings["clientId"],
            $openIdSettings["issuer"],
            $openIdSettings["tokenEndpoint"],
            $openIdSettings["redirectUri"]
        );
    }
}