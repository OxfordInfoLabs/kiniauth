<?php

namespace Kiniauth\ValueObjects\Security\SSO;

class SAMLIdentityProviderConfiguration {

    private string $identityProviderEntityId;

    private string $targetUrl;

    private string $identityProvider_x509cert;

    /**
     * @param string $identityProviderEntityId
     * @param string $targetUrl
     * @param string $identityProvider_x509cert
     */
    public function __construct(string $identityProviderEntityId, string $targetUrl, string $identityProvider_x509cert) {
        $this->identityProviderEntityId = $identityProviderEntityId;
        $this->targetUrl = $targetUrl;
        $this->identityProvider_x509cert = $identityProvider_x509cert;
    }

    public function returnSettings(): array {
        return [
            "entityId" => $this->identityProviderEntityId,
            "singleSignOnService" => [
                "url" => $this->targetUrl,
                "binding" => "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
            ],
            "x509cert" => $this->identityProvider_x509cert,
        ];
    }

}