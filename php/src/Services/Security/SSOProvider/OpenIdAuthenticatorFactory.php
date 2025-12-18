<?php

namespace Kiniauth\Services\Security\SSOProvider;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Application\SettingsService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\JWT\JWTManager;
use Kiniauth\ValueObjects\Security\SSO\OpenIdAuthenticatorConfiguration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
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

        $jwtManager = new JWTManager($config->getJwtAlg(), $config->getJwtSecret());

        return new OpenIdAuthenticator(
            $requestDispatcher,
            $session,
            $config,
            $jwtManager
        );
    }

    /**
     * @param string $providerKey
     * @returns OpenIdAuthenticatorConfiguration
     */
    private function getConfiguration(string $providerKey) {

        /**
         * Lookup the OpenID settings for the account based on supplied provider.
         * @var Account[] $accounts
         */
        $accounts = Container::instance()->get(ActiveRecordInterceptor::class)->executeInsecure(function () use ($providerKey) {
            return Account::filter("WHERE settings LIKE ?", "%\"provider\":\"$providerKey\"%");
        });

        if (sizeof($accounts) > 0) {
            $accountSettings = $accounts[0]->getSettings();
            $oidcSettings = $accountSettings["oidc"] ?? null;
        } else {
            throw new AccessDeniedException("Key not found");
        }

        $settingsService = Container::instance()->get(SettingsService::class);
        $backendUrl = $settingsService->getSettingValue("backendUrl");

        $config = new OpenIdAuthenticatorConfiguration(
            $oidcSettings["clientId"],
            $oidcSettings["issuer"],
            $oidcSettings["authorizationEndpoint"],
            $oidcSettings["tokenExchangeEndpoint"],
            "$backendUrl/guest/auth/oidc/$providerKey"
        );

        if (isset($oidcSettings["jwtAlg"])) {
            $config->setJwtAlg($oidcSettings["jwtAlg"]);
            $config->setJwtSecret($oidcSettings["jwtSecret"]);
        }

        return $config;
    }
}