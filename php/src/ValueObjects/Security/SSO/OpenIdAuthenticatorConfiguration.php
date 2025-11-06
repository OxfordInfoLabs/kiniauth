<?php

namespace Kiniauth\ValueObjects\Security\SSO;

use Kinikit\Core\Configuration\Configuration;

class OpenIdAuthenticatorConfiguration {

    private string $clientId;

    private string $issuer;

    private string $tokenEndpoint;

    private string $userInfoEndpoint;

    private string $redirectUri;

    public function __construct(string $provider) {
        $this->clientId = Configuration::readParameter("sso.$provider.clientId");
        $this->issuer = Configuration::readParameter("sso.$provider.issuer");
        $this->tokenEndpoint = Configuration::readParameter("sso.$provider.tokenEndpoint");
        $this->userInfoEndpoint = Configuration::readParameter("sso.$provider.userInfoEndpoint");
        $this->redirectUri = Configuration::readParameter("sso.$provider.redirectUri");
    }

    public function getClientId(): string {
        return $this->clientId;
    }

    public function getIssuer(): string {
        return $this->issuer;
    }

    public function getTokenEndpoint(): string {
        return $this->tokenEndpoint;
    }

    public function getUserInfoEndpoint(): string {
        return $this->userInfoEndpoint;
    }

    public function getRedirectUri(): string {
        return $this->redirectUri;
    }

}