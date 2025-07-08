<?php

namespace Kiniauth\Test\Services\ImportExport\ManagedProject;

use Kiniauth\Objects\Account\ProjectSummary;
use Kiniauth\Objects\ImportExport\ManagedProject\ManagedProject;
use Kiniauth\Objects\ImportExport\ManagedProject\ManagedProjectTargetAccount;
use Kiniauth\Objects\ImportExport\ManagedProject\ManagedProjectVersion;
use Kiniauth\Services\Account\ProjectService;
use Kiniauth\Services\ImportExport\ImportExportService;
use Kiniauth\Services\ImportExport\ManagedProject\ManagedProjectService;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

include_once "autoloader.php";

class ManagedProjectServiceTest extends TestBase {

    private $importExportServiceMock;
    private $projectService;
    private $service;

    protected function setUp(): void {
        $this->importExportServiceMock = MockObjectProvider::mock(ImportExportService::class);
        $this->projectService = MockObjectProvider::mock(ProjectService::class);
        $this->service = new ManagedProjectService($this->importExportServiceMock, $this->projectService);
    }

    public function testGetManagedProjectFetchesById() {

        $managedProject = new ManagedProject("my managed project", 20, "projectKey");
        $managedProject->save();

        $id = $managedProject->getId();

        $this->assertEquals($managedProject, $this->service->getManagedProject($id));

    }

    public function testSearchForManagedProjectsSearchesCorrectly() {

        $result = $this->service->searchForManagedProjects(2, 'soapSuds');

        $this->assertCount(1, $result);
        $this->assertSame(2, $result[0]->getSourceAccountId());
        $this->assertSame("soapSuds", $result[0]->getSourceProjectKey());

    }

    public function testCreateManagedProjectCreatesAndReturnsId() {

        $id = $this->service->createManagedProject('Project X', 55, 'myKey');

        /** @var ManagedProject $managedProject */
        $managedProject = ManagedProject::fetch($id);

        $this->assertEquals("Project X", $managedProject->getName());
        $this->assertEquals(55, $managedProject->getSourceAccountId());
        $this->assertEquals("myKey", $managedProject->getSourceProjectKey());

    }

    public function testExportAndUpdateCreatesVersionAndIssuesUpdate() {

        $expectedExport = new ProjectExport(null,null);
        $this->importExportServiceMock->returnValue("exportProject", $expectedExport, ["soapSuds", null, 2]);

        $versionId = $this->service->exportAndUpdate(1);

        /** @var ManagedProjectVersion $newVersion */
        $newVersion = ManagedProjectVersion::fetch($versionId);

        $this->assertEquals(1, $newVersion->getManagedProjectId());
        $this->assertEquals($expectedExport, $newVersion->getProjectExport());
        $this->assertEquals((new \DateTime())->format("Y-m-d H:i:s"), $newVersion->getExportDate()->format("Y-m-d H:i:s"));

    }

    public function testDoesInstallNewProjectCorrectly() {

        $this->projectService->throwException("getProject", Container::instance()->new(ObjectNotFoundException::class));

        $this->service->installProjectOnAccount(1, 4);

        $this->assertTrue($this->projectService->methodWasCalled("saveProject", [
            new ProjectSummary("SoapSuds", "SoapSuds", "soapSuds"), 4
        ]));

        $this->assertTrue($this->importExportServiceMock->methodWasCalled("importProject", [
            "soapSuds", new ProjectExport(null,null), 4
        ]));

        // Assert added as target account
        try {
            ManagedProjectTargetAccount::fetch([1,4]);
            $this->assertTrue(true);
        } catch (ObjectNotFoundException) {
            $this->fail("Object should exist");
        }

    }

    public function testGetLatestExportReturnsExportOrNull() {

        $expectedExport = new ProjectExport(null, null);

        $projectExport = $this->service->getLatestExport(1);

        $this->assertEquals($expectedExport, $projectExport);
    }
}