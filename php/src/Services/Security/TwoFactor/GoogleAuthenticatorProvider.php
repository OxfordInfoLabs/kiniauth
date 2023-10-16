<?php

namespace Kiniauth\Services\Security\TwoFactor;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Dolondro\GoogleAuthenticator\GoogleAuthenticator;
use Dolondro\GoogleAuthenticator\QrImageGenerator\GoogleQrImageGenerator;
use Dolondro\GoogleAuthenticator\SecretFactory;
use Kiniauth\Objects\MetaData\ObjectStructuredData;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Application\ActivityLogger;
use Kiniauth\Services\MetaData\MetaDataService;
use Kiniauth\ValueObjects\Security\TwoFactor\GoogleAuthenticatorTwoFactorData;
use Kiniauth\ValueObjects\Security\UserExtended;
use Kinikit\Core\Util\StringUtils;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class GoogleAuthenticatorProvider implements TwoFactorProvider {

    /**
     * @var GoogleAuthenticator
     */
    private $googleAuthenticator;


    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var MetaDataService
     */
    private $metaDataService;


    /**
     * GoogleAuthenticatorProvider constructor.
     *
     * @param GoogleAuthenticator $googleAuthenticator
     * @param UserService $userService
     * @param MetaDataService $metaDataService
     */
    public function __construct($googleAuthenticator, $userService, $metaDataService) {
        $this->googleAuthenticator = $googleAuthenticator;
        $this->userService = $userService;
        $this->metaDataService = $metaDataService;
    }


    /**
     * Always return true when using Google 2FA
     *
     * @param User $pendingUser
     * @param mixed $twoFactorClientData
     * @return bool
     */
    public function generateTwoFactorIfRequired($pendingUser, $twoFactorClientData) {

        // Check to see whether this user has a two factor configuration
        $configItems = $this->metaDataService->getStructuredDataItemsForObjectAndType(User::class, $pendingUser->getId(), "2FASecretKey");

        return sizeof($configItems) > 0;
    }


    /**
     * Authenticate the 2FA using this provider
     *
     * @param User $pendingUser
     * @param mixed $pendingTwoFactorData
     * @param mixed $twoFactorLoginData
     * @return mixed|void
     */
    public function authenticate($pendingUser, $pendingTwoFactorData, $twoFactorLoginData) {

        // Ensure we only process codes of length 6 for auth
        if (strlen($twoFactorLoginData) == 6) {

            $filesystemAdapter = new Local(sys_get_temp_dir() . "/");
            $filesystem = new Filesystem($filesystemAdapter);
            $pool = new FilesystemCachePool($filesystem);
            $this->googleAuthenticator->setCache($pool);

            // Get secret key
            $secretKey = $this->metaDataService->getStructuredDataItem(User::class, $pendingUser->getId(), "2FASecretKey", "2FASecretKey");

            return $this->googleAuthenticator->authenticate($secretKey->getData(), $twoFactorLoginData);
        } // Process length 9 codes as backup codes
        else if (strlen($twoFactorLoginData) == 9) {

            // Check if we have a match within the backup codes
            try {
                $this->metaDataService->getStructuredDataItem(User::class, $pendingUser->getId(), "2FABackupCode", $twoFactorLoginData);
                $this->metaDataService->removeStructuredDataItem(User::class, $pendingUser->getId(), "2FABackupCode", $twoFactorLoginData);
                return true;
            } catch (ObjectNotFoundException $e) {
                return false;
            }

        } else {
            return false;
        }

    }


    /**
     * Enable two factor for the supplied user.
     *
     * @param string $userId
     */
    public function enableTwoFactor($userId = User::LOGGED_IN_USER) {

        // Generate 10 backup codes
        $backupCodeItems = [];
        $backupCodes = [];
        for ($i = 0; $i < 10; $i++) {
            $backupCode = StringUtils::generateRandomString(9, false);
            $backupCodes[] = $backupCode;
            $backupCodeItems[] =
                new ObjectStructuredData(User::class, $userId, "2FABackupCode",
                    $backupCode, null);
        }

        $user = $this->userService->getUser($userId);

        // Generate a secret key
        $secretFactory = new SecretFactory();
        $secret = $secretFactory->create("", $user->getEmailAddress());
        $secretKey = $secret->getSecretKey();

        // Generate a QR code
        $qrImageGenerator = new GoogleQrImageGenerator();
        $qrCode = $qrImageGenerator->generateUri($secret);

        // Update backup codes
        $this->metaDataService->replaceStructuredDataItems($backupCodeItems);

        // Update secret key
        $this->metaDataService->updateStructuredDataItems([
            new ObjectStructuredData(User::class, $userId, "2FASecretKey", "2FASecretKey", $secretKey)
        ]);

        // Log the fact 2FA has been enabled
        ActivityLogger::log("User 2FA enabled", null, null, [], $userId);

        return new GoogleAuthenticatorTwoFactorData($secretKey, $qrCode, $backupCodes);


    }


    /**
     * Disable two factor for the supplied user
     *
     * @param string $userId
     * @return UserExtended
     */
    public function disableTwoFactor($userId = User::LOGGED_IN_USER) {

        // Remove all structured data entries for 2FA
        $this->metaDataService->removeStructuredDataItemsForObjectAndType(User::class, $userId, "2FASecretKey");
        $this->metaDataService->removeStructuredDataItemsForObjectAndType(User::class, $userId, "2FABackupCode");

        ActivityLogger::log("User 2FA disabled", null, null, [], $userId);

    }


    private function authenticateWithGA($secretKey, $twoFactorData, $twoFactorLoginData) {
        $filesystemAdapter = new Local(sys_get_temp_dir() . "/");
        $filesystem = new Filesystem($filesystemAdapter);
        $pool = new FilesystemCachePool($filesystem);
        $this->googleAuthenticator->setCache($pool);

        return $this->googleAuthenticator->authenticate($secretKey, $twoFactorData);
    }


    private function toBEREIMPLEMENTED() {
        if (strlen($code) === 6) {

            $secretKey = $pendingUser->getTwoFactorData();

            if (!$secretKey || !$pendingUser) return false;

            $authenticated = $this->twoFactorProvider->authenticate($secretKey, $code);

            if ($authenticated) {
                $this->session->__setPendingLoggedInUser(null);

                $this->securityService->logIn($pendingUser);
                ActivityLogger::log("Logged in");
                return true;
            }
        } else if (strlen($code) === 9) {
            $backupCodes = $pendingUser->getBackupCodes();

            if (($key = array_search($code, $backupCodes)) !== false) {
                unset($backupCodes[$key]);
                $user = User::fetch($pendingUser->getId());
                $user->setBackupCodes(array_values($backupCodes));
                $user->save();

                $this->session->__setPendingLoggedInUser(null);

                $this->securityService->logIn($pendingUser);
                ActivityLogger::log("Logged in");
                return true;
            }
        }
    }

}
