<?php

namespace Kiniauth\Services\Security;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\KeyPair;
use Kiniauth\Objects\Security\KeyPairSummary;
use Kiniauth\ValueObjects\Security\KeyPairSigningOutputFormat;
use Kiniauth\ValueObjects\Util\LabelValue;
use Kinikit\Core\Configuration\Configuration;

class KeyPairService {

    /**
     * Generate a new key pair using a description for a project and account
     *
     * @param $projectKey
     * @param $accountId
     * @return int
     */
    public function generateKeyPair($description, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        list($privateKey, $publicKey) = $this->generateKeyPairKeys();

        $keyPairObj = new KeyPair(new KeyPairSummary($description, $privateKey, $publicKey), $projectKey, $accountId);
        $keyPairObj->save();

        // Return id for key pair
        return $keyPairObj->getId();

    }


    /**
     * Return a full key pair by id
     *
     * @param $keyPairId
     * @return KeyPairSummary
     */
    public function getKeyPair($keyPairId) {
        return KeyPair::fetch($keyPairId)?->toSummary();
    }

    /**
     * Delete a key pair by id
     *
     * @param $keyPairId
     */
    public function deleteKeyPair($keyPairId) {
        KeyPair::fetch($keyPairId)->remove();
    }


    /**
     * @param $projectKey
     * @param $accountId
     * @return LabelValue[]
     */
    public function listKeyPairs($projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {


        // Add clauses depending on input
        $clauses = ["accountId = ?"];
        $values = [$accountId];

        if ($projectKey) {
            $clauses[] = "projectKey = ?";
            $values[] = $projectKey;
        }

        // Run query and return label value objects
        $matches = KeyPair::filter("WHERE " . join(" AND ", $clauses) . " ORDER BY description", $values);

        return LabelValue::generateFromObjectArray($matches, "description", "id");

    }

    /**
     * Sign data using the private key for the key pair passed in. Returns a Hex signature
     *
     * @param $data
     * @param $keyPairId
     *
     * @return string
     */
    public function signData($data, $keyPairId, $outputFormat = KeyPairSigningOutputFormat::Hex) {
        $keyPair = $this->getKeyPair($keyPairId);
        openssl_sign($data, $signature, $keyPair->getPrivateKey());

        switch ($outputFormat) {
            case KeyPairSigningOutputFormat::Hex:
                return bin2hex($signature);
            case KeyPairSigningOutputFormat::Base64:
                return base64_encode($signature);
        }

    }

    /**
     * Generate key pair keys
     *
     * @return array
     */
    private function generateKeyPairKeys(): array {
        $config = [
            "digest_alg" => Configuration::readParameter("keypair.algorithm") ?? "sha512",
            "private_key_bits" => Configuration::readParameter("keypair.key.bits") ?? 2048,
            "private_key_type" => Configuration::readParameter("keypair.key.type") ?? OPENSSL_KEYTYPE_RSA
        ];

        // Generate keypair
        $keyPair = openssl_pkey_new($config);

        // Get the private key
        openssl_pkey_export($keyPair, $privateKey);

        // Get the public key
        $details = openssl_pkey_get_details($keyPair);
        $publicKey = $details["key"];

        return array($privateKey, $publicKey);
    }


}