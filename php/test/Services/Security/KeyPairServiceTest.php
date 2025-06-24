<?php

namespace Kiniauth\Test\Services\Security;

use Kiniauth\Objects\Security\KeyPair;
use Kiniauth\Objects\Security\KeyPairSummary;
use Kiniauth\Services\Security\KeyPairService;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\Util\LabelValue;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

include_once "autoloader.php";

class KeyPairServiceTest extends TestBase {

    /**
     * @var KeyPairService
     */
    private $keyPairService;


    public function setUp(): void {
        $this->keyPairService = Container::instance()->get(KeyPairService::class);
    }

    public function testCanGenerateNewKeyPairWithDefaultValuesRetrieveCheckAndRemoveIt() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $keyPairId = $this->keyPairService->generateKeyPair("Main one", null, 1);
        $this->assertNotNull($keyPairId);

        $keyPair = $this->keyPairService->getKeyPair($keyPairId);
        $this->assertNotNull($keyPair->getPrivateKey());
        $this->assertNotNull($keyPair->getPublicKey());
        $this->assertStringContainsString("BEGIN PRIVATE KEY", $keyPair->getPrivateKey());
        $this->assertStringContainsString("BEGIN PUBLIC KEY", $keyPair->getPublicKey());

        // Sign and verify to confirm that a valid key pair was generated
        openssl_sign("BINGO WAS HIS NAME OH", $signature, $keyPair->getPrivateKey());
        $this->assertEquals(1, openssl_verify("BINGO WAS HIS NAME OH", $signature, $keyPair->getPublicKey()));

        // Delete the key
        $this->keyPairService->deleteKeyPair($keyPairId);

        try {
            $this->keyPairService->getKeyPair($keyPairId);
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            // Correct
        }

    }

    public function testCanListKeyPairsInProjectAndAccountAsLabelValueObjects() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $keyPairId1 = $this->keyPairService->generateKeyPair("Main one", null, 2);
        $keyPairId2 = $this->keyPairService->generateKeyPair("Second one", "soapSuds", 2);
        $keyPairId3 = $this->keyPairService->generateKeyPair("Third one", "soapSuds", 2);

        $this->assertEquals([
            new LabelValue("Main one", $keyPairId1),
            new LabelValue("Second one", $keyPairId2),
            new LabelValue("Third one", $keyPairId3)
        ], $this->keyPairService->listKeyPairs(null, 2));

        $this->assertEquals([
            new LabelValue("Second one", $keyPairId2),
            new LabelValue("Third one", $keyPairId3)
        ], $this->keyPairService->listKeyPairs("soapSuds", 2));


    }


    public function testCanSignDataUsingKeyPair() {


        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $keyPairId = $this->keyPairService->generateKeyPair("Main one", null, 1);

        // Grab key pair for comparison
        $keyPair = $this->keyPairService->getKeyPair($keyPairId);

        // Sign some data and get the signature
        $signature = $this->keyPairService->signData("THE EMPIRE STRIKES BACK", $keyPairId);

        // Now use the public key to verify the signature
        $this->assertEquals(1, openssl_verify("THE EMPIRE STRIKES BACK", hex2bin($signature), $keyPair->getPublicKey()));


    }


}