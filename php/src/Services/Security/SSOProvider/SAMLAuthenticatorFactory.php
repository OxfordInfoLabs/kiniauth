<?php

namespace Kiniauth\Services\Security\SSOProvider;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\Application\SettingsService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\ValueObjects\Security\SSO\SAMLAuthenticatorConfiguration;
use Kiniauth\ValueObjects\Security\SSO\SAMLIdentityProviderConfiguration;
use Kiniauth\ValueObjects\Security\SSO\SAMLServiceProviderConfiguration;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Settings;

class SAMLAuthenticatorFactory implements AuthenticatorFactory {

    /**
     * Creates and returns a fully configured SAMLAuthenticator instance.
     *
     * @param string $provider The identifier for the SSO provider
     * @return SAMLAuthenticator
     */
    public function create(string $providerKey): SAMLAuthenticator {

        $config = $this->getConfiguration($providerKey);
        $settings = $config->returnSettings();

        $authenticator = new SAMLAuthenticator(
            new Settings($settings, true),
            new Auth($settings)
        );

        return $authenticator;
    }

    public function getServiceProviderMetadata() {
        $serviceProviderConfig = $this->getServiceProviderConfiguration();
        $settings = new Settings(["sp" => $serviceProviderConfig->returnSettings()]);
        return $settings->getSPMetadata();
    }

    private function getConfiguration(string $providerKey) {
        return new SAMLAuthenticatorConfiguration(
            $this->getServiceProviderConfiguration(),
            $this->getIdentityProviderConfiguration($providerKey)
        );
    }

    private function getServiceProviderConfiguration() {
        $settingsService = Container::instance()->get(SettingsService::class);

        $backendUrl = $settingsService->getSettingValue("backendURL");
        $entityId = $backendUrl . "/guest/auth/saml/metadata";
        $acsUrl = $backendUrl . "/guest/auth/sso/saml";

        $x509cert = file_get_contents(Configuration::readParameter("saml.path.x509cert"));
        $privateKey = file_get_contents(Configuration::readParameter("saml.path.privateKey"));
        return new SAMLServiceProviderConfiguration($entityId, $acsUrl, $x509cert, $privateKey);
    }

    private function getIdentityProviderConfiguration(string $providerKey) {

        /**
         * Lookup the OpenID settings for the account based on supplied provider.
         * @var Account[] $accounts
         */
        $accounts = Container::instance()->get(ActiveRecordInterceptor::class)->executeInsecure(function () use ($providerKey) {
            return Account::filter("WHERE settings LIKE ?", "%\"provider\":\"$providerKey\"%");
        });

        if (sizeof($accounts) > 0) {
            $accountSettings = $accounts[0]->getSettings();
            $samlSettings = $accountSettings["saml"];
        } else {
            return null;
        }

        return new SAMLIdentityProviderConfiguration(
            $samlSettings["entityId"],
            $samlSettings["ssoUrl"],
            $samlSettings["x509cert"]
        );

    }
}