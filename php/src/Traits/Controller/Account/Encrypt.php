<?php

namespace Kiniauth\Traits\Controller\Account;

use Kiniauth\Services\Security\EncryptionService;
use Kinikit\Core\Configuration\Configuration;

trait Encrypt {

    private $encryptionService;

    /**
     * @param $encryptionService EncryptionService
     */
    public function __construct(EncryptionService $encryptionService) {
        $this->encryptionService = $encryptionService;
    }

    /**
     * Return an encrypted string for the provided sso authentication
     *
     * @http POST /sso
     *
     * @param $payload - Containing: authenticatorKey - Key for the authentication type eg. oidc, saml
     *                               text - The text to be encrypted
     *
     * Returns the base 64 encrypted string
     *
     * @return string
     */
    public function encryptStringForSSO($payload) {
        $authenticatorKey = $payload["authenticatorKey"];
        $masterKey = Configuration::readParameter("sso.$authenticatorKey.masterKey");
        return $this->encryptionService->encrypt($masterKey, $payload["text"]);
    }

}
