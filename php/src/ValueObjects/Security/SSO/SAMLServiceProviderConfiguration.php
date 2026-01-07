<?php

namespace Kiniauth\ValueObjects\Security\SSO;

class SAMLServiceProviderConfiguration {

    private string $entityId;

    private string $acsUrl;

    private string $x509cert;

    private string $privateKey;

    /**
     * @param string $entityId
     * @param string $acsUrl
     * @param string $x509cert
     * @param string $privateKey
     */
    public function __construct(string $entityId, string $acsUrl, string $x509cert, string $privateKey) {
        $this->entityId = $entityId;
        $this->acsUrl = $acsUrl;
        $this->x509cert = $x509cert;
        $this->privateKey = $privateKey;
    }

    public function returnSettings(): array {
        return [
            "strict" => true,
            "debug" => false,
            "entityId" => $this->entityId,
            "assertionConsumerService" => [
                "url" => $this->acsUrl,
                "binding" => "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
            ],
            "x509cert" => $this->x509cert,
            "privateKey" => $this->privateKey
        ];
    }
}