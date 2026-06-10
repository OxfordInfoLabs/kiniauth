<?php

namespace Kiniauth\ValueObjects\Security\SSO;

class SAMLAuthenticatorConfiguration {

    private SAMLServiceProviderConfiguration $spConfig;

    private SAMLIdentityProviderConfiguration $idpConfig;

    private string $baseUrl;

    /**
     * @param SAMLServiceProviderConfiguration $spConfig
     * @param SAMLIdentityProviderConfiguration $idpConfig
     */
    public function __construct(SAMLServiceProviderConfiguration $spConfig, SAMLIdentityProviderConfiguration $idpConfig, string $baseUrl) {
        $this->spConfig = $spConfig;
        $this->idpConfig = $idpConfig;
        $this->baseUrl = $baseUrl;
    }

    public function returnSettings(): array {
        return [
            "strict" => true,
            "debug" => false,
            "baseurl" => $this->baseUrl,
            "sp" => $this->spConfig->returnSettings(),
            "idp" => $this->idpConfig->returnSettings(),
            'security' => [
                'checkDestinationExists' => true,
                'authnRequestsSigned' => true,
                'wantAssertionsSigned' => true,
                'wantMessagesSigned' => false,
                'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
                'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
            ]
        ];
    }

}