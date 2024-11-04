<?php

namespace Kiniauth\Test\Services\ImportExport;

use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\Services\ImportExport\DefaultProjectImporter;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kiniauth\ValueObjects\ImportExport\ProjectExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectImportAnalysis;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;

include_once "autoloader.php";

class DefaultProjectImporterTest extends TestBase {

    /**
     * @var DefaultProjectImporter
     */
    private $importer;

    /**
     * @var MockObject|NotificationService
     */
    private $notificationService;


    public function setUp(): void {
        $this->notificationService = MockObjectProvider::mock(NotificationService::class);
        $this->importer = new DefaultProjectImporter($this->notificationService);
    }


    public function testCanAnalyseImportFromProjectExportWhereNoNotificationGroupsExist() {

        $projectExport = new ProjectExport(new ProjectExportConfig([]), [
            new NotificationGroup(new NotificationGroupSummary("Group 1", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 1), null, null),
            new NotificationGroup(new NotificationGroupSummary("Group 2", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 2), null, null)
        ]);

        // Assume no existent groups
        $this->notificationService->returnValue("listNotificationGroups", [], [
            PHP_INT_MAX, 0, "testProject", 5
        ]);

        $analysis = $this->importer->analyseImport(5, "testProject", $projectExport);
        $this->assertEquals(new ProjectImportAnalysis(
            date("Y-m-d H:i:s"),
            [
            "Notification Groups" => [
                new ProjectImportResource(1, "Group 1", ProjectImportResourceStatus::Create),
                new ProjectImportResource(2, "Group 2", ProjectImportResourceStatus::Create),
            ]
        ]), $analysis);


    }


    public function testCanAnalyseImportFromProjectExportWhereExistingNotificationGroupExist() {

        $projectExport = new ProjectExport(new ProjectExportConfig([]), [
            new NotificationGroup(new NotificationGroupSummary("Group 1", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 1), null, null),
            new NotificationGroup(new NotificationGroupSummary("Group 2", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 2), null, null)
        ]);

        // Assume existent group
        $this->notificationService->returnValue("listNotificationGroups", [
            new NotificationGroupSummary("Group 1", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 5)
        ], [
            PHP_INT_MAX, 0, "testProject", 5
        ]);

        $analysis = $this->importer->analyseImport(5, "testProject", $projectExport);
        $this->assertEquals(new ProjectImportAnalysis(
            date("Y-m-d H:i:s"),
            [
            "Notification Groups" => [
                new ProjectImportResource(1, "Group 1", ProjectImportResourceStatus::Ignore),
                new ProjectImportResource(2, "Group 2", ProjectImportResourceStatus::Create),
            ]
        ]), $analysis);


    }


    public function testCanImportNotificationGroupsFromProjectExportWhereNoneExists() {

        $projectExport = new ProjectExport(new ProjectExportConfig([]), [
            new NotificationGroup(new NotificationGroupSummary("Group 1", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 1), null, null),
            new NotificationGroup(new NotificationGroupSummary("Group 2", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 2), null, null)
        ]);

        // Assume no existent groups
        $this->notificationService->returnValue("listNotificationGroups", [], [
            PHP_INT_MAX, 0, "testProject", 5
        ]);

        $this->importer->importProject(5, "testProject", $projectExport);


        $summary1 = new NotificationGroup(new NotificationGroupSummary("Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL), null, null);
        $summary2 = new NotificationGroup(new NotificationGroupSummary("Group 2", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL), null, null);


        // Check groups were created
        $this->assertTrue($this->notificationService->methodWasCalled("saveNotificationGroup", [
            $summary1, "testProject", 5
        ]));

        $this->assertTrue($this->notificationService->methodWasCalled("saveNotificationGroup", [
            $summary2, "testProject", 5
        ]));


    }


    public function testExistingNotificationGroupsWithSameTitleAreLeftIntactFromProjectExport() {

        $projectExport = new ProjectExport(new ProjectExportConfig([]), [
            new NotificationGroup(new NotificationGroupSummary("Group 1", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 1), null, null),
            new NotificationGroup(new NotificationGroupSummary("Group 2", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 2), null, null)
        ]);

        // Assume no existent groups
        $this->notificationService->returnValue("listNotificationGroups", [
            new NotificationGroupSummary("Group 1", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 5)
        ], [
            PHP_INT_MAX, 0, "testProject", 5
        ]);

        $this->importer->importProject(5, "testProject", $projectExport);


        $summary1 = new NotificationGroup(new NotificationGroupSummary("Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL), null, null);
        $summary2 = new NotificationGroup(new NotificationGroupSummary("Group 2", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL), null, null);


        // Check groups were created
        $this->assertFalse($this->notificationService->methodWasCalled("saveNotificationGroup", [
            $summary1, "testProject", 5
        ]));

        $this->assertTrue($this->notificationService->methodWasCalled("saveNotificationGroup", [
            $summary2, "testProject", 5
        ]));


    }
}