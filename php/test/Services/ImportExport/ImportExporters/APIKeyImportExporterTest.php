<?php

namespace Kiniauth\Test\Services\ImportExport\ImportExporters;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Objects\Security\APIKeyRole;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\ImportExport\ImportExporters\APIKeyImportExporter;
use Kiniauth\Services\Security\APIKeyService;
use Kiniauth\Services\Security\RoleService;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ExportObjects\ExportedApiKey;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kiniauth\ValueObjects\Security\ScopeObjectRolesAssignment;
use Kinikit\Core\Testing\MockObjectProvider;

include_once "autoloader.php";

class APIKeyImportExporterTest extends TestBase {

    /**
     * @var APIKeyImportExporter
     */
    private $importExporter;

    /**
     * @var APIKeyService
     */
    private $apiKeyService;


    /**
     * @var RoleService
     */
    private $roleService;


    public function setUp(): void {
        $this->apiKeyService = MockObjectProvider::mock(APIKeyService::class);
        $this->roleService = MockObjectProvider::mock(RoleService::class);
        $this->importExporter = new APIKeyImportExporter($this->apiKeyService, $this->roleService);

    }

    public function testCanGetGetExportableResources() {

        $this->apiKeyService->returnValue("listAPIKeys", [
            new APIKey("Test Key 1", [new APIKeyRole(Role::SCOPE_PROJECT, "testProject", 5, 5)], "abcdefghijklm", "zyxwvutsrqp", null, 55),
            new APIKey("Test Key 2", [new APIKeyRole(Role::SCOPE_PROJECT, "testProject", 6, 5)], "12345678910", "10987654321", null, 66)
        ], ["testProject", 5]);

        $this->assertEquals([
            new ProjectExportResource(55, "Test Key 1", new ObjectInclusionExportConfig(true)),
            new ProjectExportResource(66, "Test Key 2", new ObjectInclusionExportConfig(true))
        ], $this->importExporter->getExportableProjectResources(5, "testProject"));


    }

    public function testCanExportResources() {


        $this->apiKeyService->returnValue("listAPIKeys", [
            new APIKey("Test Key 1", [new APIKeyRole(Role::SCOPE_PROJECT, "testProject", 5, 5)], "abcdefghijklm", "zyxwvutsrqp", null, 55),
            new APIKey("Test Key 2", [new APIKeyRole(Role::SCOPE_PROJECT, "testProject", 6, 5)], "12345678910", "10987654321", null, 66),
            new APIKey("Test Key 3", [new APIKeyRole(Role::SCOPE_PROJECT, "testProject", 5, 5), new APIKeyRole(Role::SCOPE_PROJECT, "testProject", 6, 5)], "12345678910", "10987654321", null, 77)
        ], ["testProject", 5]);


        $this->assertEquals([
            new ExportedApiKey(-1, "Test Key 1", [5]),
            new ExportedApiKey(-2, "Test Key 3", [5, 6])
        ], $this->importExporter->createExportObjects(5, "testProject", [
            55 => new ObjectInclusionExportConfig(true),
            66 => new ObjectInclusionExportConfig(false),
            77 => new ObjectInclusionExportConfig(true)
        ], []));

    }

    public function testCanAnalyseImportForExportedResources() {

        $this->apiKeyService->returnValue("listAPIKeys", [
            new APIKey("Test Key 1", [new APIKeyRole(Role::SCOPE_PROJECT, "testProject", 5, 5)], "abcdefghijklm", "zyxwvutsrqp", null, 55)
        ], ["testProject", 5]);

        $this->assertEquals([
            new ProjectImportResource(-1, "Test Key 1", ProjectImportResourceStatus::Update, 55),
            new ProjectImportResource(-2, "Test Key 3", ProjectImportResourceStatus::Create)
        ], $this->importExporter->analyseImportObjects(5, "testProject", [
            new ExportedApiKey(-1, "Test Key 1", [5]),
            new ExportedApiKey(-2, "Test Key 3", [5, 6])
        ], [-1 => new ObjectInclusionExportConfig(true),
            -2 => new ObjectInclusionExportConfig(true)]));

    }

    public function testCanImportAndCreateOrUpdateAPIKeysIntoTargetAccountFromExport() {

        $this->apiKeyService->returnValue("listAPIKeys", [
            new APIKey("Test Key 1", [new APIKeyRole(Role::SCOPE_PROJECT, "testProject", 5, 5)], "abcdefghijklm", "zyxwvutsrqp", null, 55)
        ], ["testProject", 5]);

        $this->apiKeyService->returnValue("createAPIKeyForAccountAndProject", 99, [
            "Test Key 3", "testProject", 5
        ]);


        $this->importExporter->importObjects(5, "testProject", [
            new ExportedApiKey(-1, "Test Key 1", [5]),
            new ExportedApiKey(-2, "Test Key 3", [5, 6])
        ], [-1 => new ObjectInclusionExportConfig(true),
            -2 => new ObjectInclusionExportConfig(true)]);


        $this->assertFalse($this->apiKeyService->methodWasCalled("createAPIKeyForAccountAndProject", [
            "Test Key 1", "testProject", 5
        ]));

        $this->assertTrue($this->apiKeyService->methodWasCalled("createAPIKeyForAccountAndProject", [
            "Test Key 3", "testProject", 5
        ]));

        $this->assertTrue($this->roleService->methodWasCalled("updateAssignedScopeObjectRoles", [
            Role::APPLIES_TO_API_KEY, 55, [new ScopeObjectRolesAssignment(Role::SCOPE_PROJECT, "testProject", [5])], 5
        ]));


        $this->assertTrue($this->roleService->methodWasCalled("updateAssignedScopeObjectRoles", [
            Role::APPLIES_TO_API_KEY, 99, [new ScopeObjectRolesAssignment(Role::SCOPE_PROJECT, "testProject", [5, 6])], 5
        ]));

    }


}