<?php

namespace Kiniauth\Services\Security\SSOProvider;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\JWT\JWTManager;
use Kiniauth\ValueObjects\Security\SSO\OpenIdAuthenticatorConfiguration;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;

class OpenIdAuthenticatorFactory implements AuthenticatorFactory {

    /**
     * Creates and returns a fully configured OpenIdAuthenticator instance.
     *
     * @param string $providerKey
     * @return OpenIdAuthenticator
     */
    public function create(string $providerKey): OpenIdAuthenticator {

        $requestDispatcher = Container::instance()->get(HttpRequestDispatcher::class);
        $session = Container::instance()->get(Session::class);

        $config = $this->getConfiguration($providerKey);

        $jwtSecret = Configuration::readParameter("sso.$providerKey.jwt.secret");
        $jwtAlg = Configuration::readParameter("sso.$providerKey.jwt.alg");

        $jwtManager = new JWTManager($jwtAlg, $jwtSecret);

        return new OpenIdAuthenticator(
            $requestDispatcher,
            $session,
            $config,
            $jwtManager
        );
    }

    private function getConfiguration($providerKey) {

        /**
         * Lookup the OpenID settings for the account based on supplied provider.
         * @var Account[] $accounts
         */
        $accounts = Account::filter("WHERE settings LIKE ?", "%\"provider\":\"$providerKey\"%");
        if (sizeof($accounts) > 0) {
            $accountSettings = $accounts[0]->getSettings();
            $openIdSettings = $accountSettings["openId"];
        } else {
            return null;
        }

        return new OpenIdAuthenticatorConfiguration(
            $openIdSettings["clientId"],
            $openIdSettings["issuer"],
            $openIdSettings["authorizationEndpoint"],
            $openIdSettings["tokenEndpoint"],
            $openIdSettings["redirectUri"]
        );
    }
}