<?php

namespace Kiniauth\ValueObjects\Security\SSO;

class SAMLAuthenticatorConfiguration {

    private SAMLServiceProviderConfiguration $spConfig;

    private SAMLIdentityProviderConfiguration $idpConfig;

    /**
     * @param SAMLServiceProviderConfiguration $spConfig
     * @param SAMLIdentityProviderConfiguration $idpConfig
     */
    public function __construct(SAMLServiceProviderConfiguration $spConfig, SAMLIdentityProviderConfiguration $idpConfig) {
        $this->spConfig = $spConfig;
        $this->idpConfig = $idpConfig;
    }

    public function returnSettings(): array {
        return [
            "sp" => $this->spConfig->returnSettings(),
            "idp" => $this->idpConfig->returnSettings(),
            'security' => [
                'authnRequestsSigned' => true,
                'wantAssertionsSigned' => true,
                'wantMessagesSigned' => false,
                'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
                'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
            ]
        ];
    }

}