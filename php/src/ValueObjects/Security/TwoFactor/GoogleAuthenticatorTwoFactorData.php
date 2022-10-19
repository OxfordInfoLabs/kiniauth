<?php


namespace Kiniauth\ValueObjects\Security\TwoFactor;


class GoogleAuthenticatorTwoFactorData {

    /**
     * Secret key
     *
     * @var string
     */
    private $secretKey;


    /**
     * QR Code URL
     *
     * @var string
     */
    private $qrCodeURL;


    /**
     * @var string[]
     */
    private $backupCodes;

    /**
     * GoogleAuthenticatorTwoFactorData constructor.
     *
     * @param string $secretKey
     * @param string $qrCodeURL
     * @param string[] $backupCodes
     */
    public function __construct($secretKey, $qrCodeURL, $backupCodes) {
        $this->secretKey = $secretKey;
        $this->qrCodeURL = $qrCodeURL;
        $this->backupCodes = $backupCodes;
    }


    /**
     * @return string
     */
    public function getSecretKey() {
        return $this->secretKey;
    }

    /**
     * @return string
     */
    public function getQrCodeURL() {
        return $this->qrCodeURL;
    }

    /**
     * @return string[]
     */
    public function getBackupCodes() {
        return $this->backupCodes;
    }


}