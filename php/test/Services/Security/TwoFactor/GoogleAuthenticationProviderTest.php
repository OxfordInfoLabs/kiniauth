<?php


namespace Kiniauth\Test\Services\Security\TwoFactor;


use Dolondro\GoogleAuthenticator\GoogleAuthenticator;
use Kiniauth\Objects\MetaData\ObjectStructuredData;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\MetaData\MetaDataService;
use Kiniauth\Services\Security\TwoFactor\GoogleAuthenticatorProvider;
use Kiniauth\Test\TestBase;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;

include_once "autoloader.php";

class GoogleAuthenticationProviderTest extends TestBase {

    /**
     * @var GoogleAuthenticatorProvider
     */
    private $provider;


    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var MockObject
     */
    private $metaDataService;

    /**
     * @var MockObject
     */
    private $googleAuthenticator;


    public function setUp(): void {

        $this->userService = MockObjectProvider::instance()->getMockInstance(UserService::class);
        $this->metaDataService = MockObjectProvider::instance()->getMockInstance(MetaDataService::class);
        $this->googleAuthenticator = MockObjectProvider::instance()->getMockInstance(GoogleAuthenticator::class);

        $this->provider = new GoogleAuthenticatorProvider($this->googleAuthenticator, $this->userService, $this->metaDataService);
    }


    public function testCanEnableTwoFactorForAUser() {

        $this->userService->returnValue("getUser", new User("test@hello.com"), [3]);

        // Enable two factor for a user
        $twoFactorData = $this->provider->enableTwoFactor(3);

        $this->assertEquals(16, strlen($twoFactorData->getSecretKey()));
        $this->assertEquals("https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth://totp/%3Atest%40hello.com?secret={$twoFactorData->getSecretKey()}&issuer=", $twoFactorData->getQrCodeURL());
        $backupCodes = $twoFactorData->getBackupCodes();

        // Check we stored the backup codes
        $replaceMethodCalls = $this->metaDataService->getMethodCallHistory("replaceStructuredDataItems");
        $this->assertEquals(1, sizeof($replaceMethodCalls));

        $backupCodesCall = $replaceMethodCalls[0];
        $this->assertEquals(10, sizeof($backupCodesCall[0]));
        foreach ($backupCodesCall[0] as $index => $backupCodeItem) {

            $this->assertEquals(User::class, $backupCodeItem->getObjectType());
            $this->assertEquals(3, $backupCodeItem->getObjectId());
            $this->assertEquals("2FABackupCode", $backupCodeItem->getDataType());
            $this->assertEquals(9, strlen($backupCodeItem->getPrimaryKey()));

            $this->assertEquals($backupCodes[$index], $backupCodeItem->getPrimaryKey());
        }

        // Check we stored the secret key
        $this->assertTrue($this->metaDataService->methodWasCalled("updateStructuredDataItems", [[
            new ObjectStructuredData(User::class, 3, "2FASecretKey", "2FASecretKey", $twoFactorData->getSecretKey())
        ]]));

    }


    public function testCanDisableTwoFactorForAUser() {

        $this->provider->disableTwoFactor(4);

        $this->assertTrue($this->metaDataService->methodWasCalled("removeStructuredDataItemsForObjectAndType", [
            User::class, 4, "2FASecretKey"
        ]));

        $this->assertTrue($this->metaDataService->methodWasCalled("removeStructuredDataItemsForObjectAndType", [
            User::class, 4, "2FABackupCode"
        ]));
    }


}