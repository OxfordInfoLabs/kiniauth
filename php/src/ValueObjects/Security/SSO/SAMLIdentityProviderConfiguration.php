<?php

namespace Kiniauth\ValueObjects\Security\SSO;

class SAMLIdentityProviderConfiguration {

    private string $identityProviderEntityId;

    private string $authorisationUrl;

    private string $identityProvider_x509cert;

    /**
     * @param string $identityProviderEntityId
     * @param string $authorisationUrl
     * @param string $identityProvider_x509cert
     */
    public function __construct(string $identityProviderEntityId, string $authorisationUrl, string $identityProvider_x509cert) {
        $this->identityProviderEntityId = $identityProviderEntityId;
        $this->authorisationUrl = $authorisationUrl;
        $this->identityProvider_x509cert = $identityProvider_x509cert;
    }

    public function returnSettings(): array {
        return [
            "entityId" => $this->identityProviderEntityId,
            "singleSignOnService" => [
                "url" => $this->authorisationUrl,
                "binding" => "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
            ],
            "x509cert" => $this->identityProvider_x509cert,
        ];
    }

}