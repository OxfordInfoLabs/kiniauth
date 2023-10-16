<?php


namespace Kiniauth\Test\Services\Security;

use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Objects\Security\APIKeyRole;
use Kiniauth\Objects\Security\APIKeySummary;
use Kiniauth\Objects\Security\Role;
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

        $apiKey1Id = $this->apiKeyService->createAPIKey("My account level API Key", [
            new APIKeyRole(Role::SCOPE_ACCOUNT, 1, 1, 1)]);

        // Check saved
        $apiKey1 = APIKey::fetch($apiKey1Id);
        $this->assertEquals("My account level API Key", $apiKey1->getDescription());
        $this->assertEquals(16, strlen($apiKey1->getAPIKey()));
        $this->assertEquals(16, strlen($apiKey1->getAPISecret()));
        $this->assertEquals(1, sizeof($apiKey1->getRoles()));
        $this->assertEquals(1, $apiKey1->getRoles()[0]->getScopeId());


        $apiKey2Id = $this->apiKeyService->createAPIKey("Another account level API Key", [
            new APIKeyRole(Role::SCOPE_ACCOUNT, 1, 1, 1)]);
        $apiKey2 = APIKey::fetch($apiKey2Id);

        $expectedRole1 = APIKeyRole::fetch([$apiKey1Id, Role::SCOPE_ACCOUNT, 1, 1]);
        $expectedRole2 = APIKeyRole::fetch([$apiKey2Id, Role::SCOPE_ACCOUNT, 1, 1]);


        $keys = $this->apiKeyService->listAPIKeys();

        $this->assertEquals([
            new APIKey("Another account level API Key", [$expectedRole2], $apiKey2->getAPIKey(), $apiKey2->getAPISecret(), User::STATUS_ACTIVE, $apiKey2Id),
            new APIKey("My account level API Key", [$expectedRole1], $apiKey1->getAPIKey(), $apiKey1->getAPISecret(), User::STATUS_ACTIVE, $apiKey1Id),
        ], $keys);

        // Now try a project specific one.

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $apiKey3Id = $this->apiKeyService->createAPIKey("Project specific one", [
            new APIKeyRole(Role::SCOPE_ACCOUNT, 2, 1, 2),
            new APIKeyRole("PROJECT", "wiperBlades", 0, 2)
        ]);
        $apiKey3 = APIKey::fetch($apiKey3Id);
        $this->assertEquals("Project specific one", $apiKey3->getDescription());
        $this->assertEquals(16, strlen($apiKey3->getAPIKey()));
        $this->assertEquals(16, strlen($apiKey3->getAPISecret()));
        $this->assertEquals(2, sizeof($apiKey3->getRoles()));
        $this->assertEquals(2, $apiKey3->getRoles()[0]->getScopeId());
        $this->assertEquals("wiperBlades", $apiKey3->getRoles()[1]->getScopeId());

        $expectedRoles = APIKeyRole::filter("WHERE apiKeyId = ?", $apiKey3Id);

        $keys = $this->apiKeyService->listAPIKeys("wiperBlades");

        $this->assertEquals([
            new APIKey("Project specific one", $expectedRoles, $apiKey3->getAPIKey(), $apiKey3->getAPISecret(), User::STATUS_ACTIVE, $apiKey3Id)
        ], $keys);

    }


    public function testCanCreateAPIKeyForAccountAndProjectUsingConvenienceFunction() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $accountOnlyKeyId = $this->apiKeyService->createAPIKeyForAccountAndProject("Account only", null);
        $projectKeyId = $this->apiKeyService->createAPIKeyForAccountAndProject("Project Focussed", "wiperBlades");

        // Check for api key roles
        $accountOnlyRoles = APIKeyRole::filter("WHERE api_key_id = ?", $accountOnlyKeyId);
        $this->assertEquals(1, sizeof($accountOnlyRoles));
        $this->assertEquals(Role::SCOPE_ACCOUNT, $accountOnlyRoles[0]->getScope());
        $this->assertEquals(2, $accountOnlyRoles[0]->getScopeId());
        $this->assertEquals(0, $accountOnlyRoles[0]->getRoleId());

        $projectRoles = APIKeyRole::filter("WHERE api_key_id = ?", $projectKeyId);
        $this->assertEquals(2, sizeof($projectRoles));
        $this->assertEquals(Role::SCOPE_ACCOUNT, $projectRoles[0]->getScope());
        $this->assertEquals(2, $projectRoles[0]->getScopeId());
        $this->assertEquals(1, $projectRoles[0]->getRoleId());
        $this->assertEquals("PROJECT", $projectRoles[1]->getScope());
        $this->assertEquals("wiperBlades", $projectRoles[1]->getScopeId());
        $this->assertEquals(0, $projectRoles[1]->getRoleId());

        $accountOnly = APIKey::fetch($accountOnlyKeyId);
        $this->assertEquals("Account only", $accountOnly->getDescription());
        $this->assertEquals($accountOnlyRoles, $accountOnly->getRoles());

        $projectKey = APIKey::fetch($projectKeyId);
        $this->assertEquals("Project Focussed", $projectKey->getDescription());
        $this->assertEquals($projectRoles, $projectKey->getRoles());


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


    public function testCanGetFirstAPIKeyWithPrivilege() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $accessKeyId = $this->apiKeyService->createAPIKey("Access Key", [new APIKeyRole(Role::SCOPE_PROJECT, "wiperBlades", 4, 2)]);
        $editKeyId = $this->apiKeyService->createAPIKey("Edit Key", [new APIKeyRole(Role::SCOPE_PROJECT, "wiperBlades", 5, 2)]);

        /**
         * @var APIKey $accessKey
         */
        $accessKey = APIKey::fetch($accessKeyId);

        /**
         * @var APIKey $editKey
         */
        $editKey = APIKey::fetch($editKeyId);

        $this->assertEquals($accessKey, $this->apiKeyService->getFirstAPIKeyWithPrivilege("access", "wiperBlades", 2));
        $this->assertEquals($editKey, $this->apiKeyService->getFirstAPIKeyWithPrivilege("editdata", "wiperBlades", 2));


    }


}