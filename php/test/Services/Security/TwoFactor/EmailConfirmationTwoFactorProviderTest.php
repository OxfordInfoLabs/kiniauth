<?php


namespace Kiniauth\Test\Services\Security\TwoFactor;


use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Objects\MetaData\ObjectStructuredData;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\MetaData\MetaDataService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\TwoFactor\EmailConfirmationTwoFactorProvider;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

include_once __DIR__ . "/../../../autoloader.php";

class EmailConfirmationTwoFactorProviderTest extends TestBase {

    /**
     * @var EmailConfirmationTwoFactorProvider
     */
    private $provider;

    /**
     * @var MockObject
     */
    private $emailService;


    /**
     * @var MockObject
     */
    private $metaDataService;


    /**
     * @var ActiveRecordInterceptor
     */
    private $activeRecordInterceptor;


    public function setUp(): void {
        $this->emailService = MockObjectProvider::instance()->getMockInstance(EmailService::class);
        $this->metaDataService = MockObjectProvider::instance()->getMockInstance(MetaDataService::class);
        $this->provider = new EmailConfirmationTwoFactorProvider($this->emailService, $this->metaDataService);

        $this->activeRecordInterceptor = Container::instance()->get(ActiveRecordInterceptor::class);

    }


    public function testIfPassedTwoFactorClientDataDoesntMatchUserStructuredDataEntryTwoFactorEmailIsSentWithReturnedSession2FACode() {


        $pendingUser = MockObjectProvider::instance()->getMockInstance(User::class);
        $pendingUser->returnValue("getId", 2);

        // Simulate no existing entry
        $this->metaDataService->throwException("getStructuredDataItem", new ObjectNotFoundException("User", "Blaah"), [User::class, 2, "2FAAuthorisedClient", 123456789]);


        $this->activeRecordInterceptor->executeInsecure(function () use ($pendingUser) {


            $code = $this->provider->generateTwoFactorIfRequired($pendingUser, "123456789");

            // Check 6 character numeric code was generated
            $this->assertIsNumeric($code);
            $this->assertEquals(6, strlen($code));

            // Check that an email was sent with the two factor code to the user
            $expectedEmail = new UserTemplatedEmail(2, "security/twofactor/two-factor-code", ["code" => $code]);

            $this->assertTrue($this->emailService->methodWasCalled("send", [
                $expectedEmail, null, 2
            ]));


        });


    }


    public function testIfPassedTwoFactorClientMatchesExistingUserStructuredDataEntryFalseIsReturnedAndNoEmailSent() {

        $pendingUser = MockObjectProvider::instance()->getMockInstance(User::class);
        $pendingUser->returnValue("getId", 2);

        // Simulate no existing entry
        $this->metaDataService->returnValue("getStructuredDataItem", new ObjectStructuredData(User::class, 2, "2FAAuthorisedClient", 123456789, null), [User::class, 2, "2FAAuthorisedClient", 123456789]);

        $response = $this->provider->generateTwoFactorIfRequired($pendingUser, "123456789");

        $this->assertFalse($response);

        $this->assertFalse($this->emailService->methodWasCalled("send"));

    }


    public function testFalseReturnedFromAuthenticateMethodIfLoginDataDoesNotMatchTwoFactorData() {

        $pendingUser = MockObjectProvider::instance()->getMockInstance(User::class);
        $pendingUser->returnValue("getId", 2);

        $response = $this->provider->authenticate($pendingUser, "STOREDVALUE", "DIFFERENTVALUE");
        $this->assertFalse($response);

    }

    public function testNewClient2FAKeyStoredAndReturnedFromAuthenticateMethodIfLoginDataMatchesTwoFactorData() {

        $pendingUser = MockObjectProvider::instance()->getMockInstance(User::class);
        $pendingUser->returnValue("getId", 2);

        $response = $this->provider->authenticate($pendingUser, "123456", "123456");
        $this->assertEquals(32, strlen($response));

        $this->assertTrue($this->metaDataService->methodWasCalled("updateStructuredDataItems", [
            [new ObjectStructuredData(User::class, 2, "2FAAuthorisedClient", $response, null)]
        ]));


    }


}