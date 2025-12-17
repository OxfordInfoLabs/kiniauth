<?php

namespace Kiniauth\ValueObjects\Security\SSO;

use Kinikit\Core\Configuration\Configuration;

class OpenIdAuthenticatorConfiguration {

    private string $clientId;

    private string $issuer;

    private string $authorisationEndpoint;

    private string $tokenEndpoint;

    private string $redirectUri;

    public function __construct(string $clientId, string $issuer, string $authorisationEndpoint, string $tokenEndpoint, string $redirectUri) {
        $this->clientId = $clientId;
        $this->issuer = $issuer;
        $this->authorisationEndpoint = $authorisationEndpoint;
        $this->tokenEndpoint = $tokenEndpoint;
        $this->redirectUri = $redirectUri;
    }

    public function getClientId(): string {
        return $this->clientId;
    }

    public function getIssuer(): string {
        return $this->issuer;
    }

    public function getAuthorisationEndpoint(): string {
        return $this->authorisationEndpoint;
    }

    public function getTokenEndpoint(): string {
        return $this->tokenEndpoint;
    }

    public function getRedirectUri(): string {
        return $this->redirectUri;
    }

}