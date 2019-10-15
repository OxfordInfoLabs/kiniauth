<?php

namespace Kiniauth\Services\Security\TwoFactor;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Dolondro\GoogleAuthenticator\GoogleAuthenticator;
use Dolondro\GoogleAuthenticator\QrImageGenerator\EndroidQrImageGenerator;
use Dolondro\GoogleAuthenticator\QrImageGenerator\GoogleQrImageGenerator;
use Dolondro\GoogleAuthenticator\Secret;
use Dolondro\GoogleAuthenticator\SecretFactory;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class GoogleAuthenticatorProvider implements TwoFactorProvider {

    private $issuer;
    private $accountName;

    private $googleAuthenticator;

    public function __construct($issuer = null, $accountName = null) {
        $this->issuer = $issuer;
        $this->accountName = $accountName ? $accountName : "new-ga" . time() . rand(0, 10);
        $this->googleAuthenticator = new GoogleAuthenticator();
    }

    public function createSecretKey() {
        $secretFactory = new SecretFactory();
        $secret = $secretFactory->create($this->issuer, $this->accountName);
        return $secret->getSecretKey();
    }

    public function generateQRCode($secretKey) {
        $secret = $this->generateSecret($secretKey);
        $qrImageGenerator = new GoogleQrImageGenerator();
        return $qrImageGenerator->generateUri($secret);
    }

    public function authenticate($secretKey, $code) {
        $filesystemAdapter = new Local(sys_get_temp_dir()."/");
        $filesystem = new Filesystem($filesystemAdapter);
        $pool = new FilesystemCachePool($filesystem);
        $this->googleAuthenticator->setCache($pool);

        return $this->googleAuthenticator->authenticate($secretKey, $code);
    }

    /**
     * @return string|null
     */
    public function getIssuer() {
        return $this->issuer;
    }

    /**
     * @param string|null $issuer
     */
    public function setIssuer($issuer) {
        $this->issuer = $issuer;
    }

    /**
     * @return string|null
     */
    public function getAccountName() {
        return $this->accountName;
    }

    /**
     * @param string|null $accountName
     */
    public function setAccountName($accountName) {
        $this->accountName = $accountName;
    }

    /**
     * This returns a Secret object which is required for generating a QR Code.
     *
     * @param $secretKey
     * @return Secret
     */
    private function generateSecret($secretKey) {
        return new Secret($this->issuer, $this->accountName, $secretKey);
    }
}
