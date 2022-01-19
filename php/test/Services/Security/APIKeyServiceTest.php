<?php


namespace Kiniauth\Test\Services\Security;

use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Objects\Security\APIKeySummary;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Security\APIKeyService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

include_once "autoloader.php";

class APIKeyServiceTest extends TestBase {

    /**
     * @var APIKeyService
     */
    private $apiKeyService;

    public function setUp(): void {
        $this->apiKeyService = Container::instance()->get(APIKeyService::class);
    }


    public function testCanCreateNewAPIKeysForAccountsAndProjectsOptionallyWithDescriptionAndListThem() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $apiKey1Id = $this->apiKeyService->createAPIKey("My account level API Key");

        // Check saved
        $apiKey1 = APIKey::fetch($apiKey1Id);
        $this->assertEquals("My account level API Key", $apiKey1->getDescription());
        $this->assertEquals(16, strlen($apiKey1->getAPIKey()));
        $this->assertEquals(16, strlen($apiKey1->getAPISecret()));
        $this->assertEquals(1, $apiKey1->getAccountId());
        $this->assertNull($apiKey1->getProjectKey());

        $apiKey2Id = $this->apiKeyService->createAPIKey("Another account level API Key");
        $apiKey2 = APIKey::fetch($apiKey2Id);

        $keys = $this->apiKeyService->listAPIKeys();

        $this->assertEquals([
            new APIKeySummary($apiKey2Id, $apiKey2->getAPIKey(), $apiKey2->getAPISecret(), "Another account level API Key", User::STATUS_ACTIVE),
            new APIKeySummary($apiKey1Id, $apiKey1->getAPIKey(), $apiKey1->getAPISecret(), "My account level API Key", User::STATUS_ACTIVE),
        ], $keys);

        // Now try a project specific one.

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $apiKey3Id = $this->apiKeyService->createAPIKey("Project specific one", "wiperBlades");
        $apiKey3 = APIKey::fetch($apiKey3Id);
        $this->assertEquals("Project specific one", $apiKey3->getDescription());
        $this->assertEquals(16, strlen($apiKey3->getAPIKey()));
        $this->assertEquals(16, strlen($apiKey3->getAPISecret()));
        $this->assertEquals(2, $apiKey3->getAccountId());
        $this->assertEquals("wiperBlades", $apiKey3->getProjectKey());

        $keys = $this->apiKeyService->listAPIKeys("wiperBlades");

        $this->assertEquals([
            new APIKeySummary($apiKey3Id, $apiKey3->getAPIKey(), $apiKey3->getAPISecret(), "Project specific one", User::STATUS_ACTIVE),
        ], $keys);

    }

    public function testCanUpdateAPIKeyDescription() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $apiKeyId = $this->apiKeyService->createAPIKey("New One");
        $apiKey = APIKey::fetch($apiKeyId);
        $this->assertEquals("New One", $apiKey->getDescription());

        $this->apiKeyService->updateAPIKeyDescription($apiKeyId, "Updated one");

        $apiKey = APIKey::fetch($apiKeyId);
        $this->assertEquals("Updated one", $apiKey->getDescription());

    }


    public function testCanRegenerateKeyAndSecret() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $apiKeyId = $this->apiKeyService->createAPIKey("New One");
        $apiKey = APIKey::fetch($apiKeyId);
        $originalKey = $apiKey->getAPIKey();
        $originalSecret = $apiKey->getAPISecret();
        $this->assertEquals(16, strlen($originalKey));
        $this->assertEquals(16, strlen($originalSecret));

        $this->apiKeyService->regenerateAPIKey($apiKeyId);

        $apiKey = APIKey::fetch($apiKeyId);
        $this->assertEquals(16, strlen($apiKey->getAPIKey()));
        $this->assertEquals(16, strlen($apiKey->getAPISecret()));
        $this->assertNotEquals($originalKey, $apiKey->getAPIKey());
        $this->assertNotEquals($originalSecret, $apiKey->getAPISecret());

    }


    public function testCanSuspendAndReactivateAPIKey() {
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $apiKeyId = $this->apiKeyService->createAPIKey("New One");
        $apiKey = APIKey::fetch($apiKeyId);
        $this->assertEquals(User::STATUS_ACTIVE, $apiKey->getStatus());

        $this->apiKeyService->suspendAPIKey($apiKeyId);
        $apiKey = APIKey::fetch($apiKeyId);
        $this->assertEquals(User::STATUS_SUSPENDED, $apiKey->getStatus());

        $this->apiKeyService->reactivateAPIKey($apiKeyId);
        $apiKey = APIKey::fetch($apiKeyId);
        $this->assertEquals(User::STATUS_ACTIVE, $apiKey->getStatus());

    }


    public function testCanRemoveExistingAPIKey() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $apiKeyId = $this->apiKeyService->createAPIKey("New One");
        APIKey::fetch($apiKeyId);

        $this->apiKeyService->removeAPIKey($apiKeyId);

        try {
            APIKey::fetch($apiKeyId);
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            $this->assertTrue(true);
        }


    }


}