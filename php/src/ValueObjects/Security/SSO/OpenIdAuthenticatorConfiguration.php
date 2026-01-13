<?php

namespace Kiniauth\ValueObjects\Security\SSO;

use Kiniauth\Services\Security\JWT\JWTAlg;

class OpenIdAuthenticatorConfiguration {

    private string $clientId;

    private string $issuer;

    private string $authorisationEndpoint;

    private string $tokenEndpoint;

    private string $redirectUri;

    private JWTAlg $jwtAlg = JWTAlg::HS256;

    private string $jwtSecret = "default";

    private string $clientSecret;

    private string $jwksUri;

    public function __construct(string $clientId, string $issuer, string $authorisationEndpoint, string $tokenEndpoint,
                                string $redirectUri, string $clientSecret, string $jwksUri) {
        $this->clientId = $clientId;
        $this->issuer = $issuer;
        $this->authorisationEndpoint = $authorisationEndpoint;
        $this->tokenEndpoint = $tokenEndpoint;
        $this->redirectUri = $redirectUri;
        $this->clientSecret = $clientSecret;
        $this->jwksUri = $jwksUri;
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

    public function getJwtAlg(): JWTAlg {
        return $this->jwtAlg;
    }

    public function setJwtAlg(JWTAlg $jwtAlg): void {
        $this->jwtAlg = $jwtAlg;
    }

    public function getJwtSecret(): string {
        return $this->jwtSecret;
    }

    public function setJwtSecret(string $jwtSecret): void {
        $this->jwtSecret = $jwtSecret;
    }

    public function getClientSecret(): string {
        return $this->clientSecret;
    }

    public function getJwksUri(): string {
        return $this->jwksUri;
    }

}