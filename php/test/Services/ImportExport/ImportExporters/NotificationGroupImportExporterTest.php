<?php

namespace Kiniauth\Test\Services\ImportExport\ImportExporters;

use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupMember;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Security\UserCommunicationData;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\Services\ImportExport\ImportExporters\NotificationGroupImportExporter;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportAnalysis;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;

include_once "autoloader.php";

class NotificationGroupImportExporterTest extends TestBase {


    /**
     * @var NotificationGroupImportExporter
     */
    private $importerExporter;

    /**
     * @var MockObject|NotificationService
     */
    private $notificationService;


    public function setUp(): void {
        $this->notificationService = MockObjectProvider::mock(NotificationService::class);
        $this->importerExporter = new NotificationGroupImportExporter($this->notificationService);
    }


    public function testExporterReturnsNotificationGroupsCorrectlyForExportableProjectResources() {
        $this->notificationService->returnValue("listNotificationGroups", [
            new NotificationGroupSummary("Example Group 1", [], [], 3),
            new NotificationGroupSummary("Example Group 2", [], [], 5),
            new NotificationGroupSummary("Example Group 3", [], [], 7)
        ], [
            PHP_INT_MAX, 0, "myProject", 7
        ]);

        $exportableResources = $this->importerExporter->getExportableProjectResources(7, "myProject");

        $this->assertEquals([
            new ProjectExportResource(3, "Example Group 1", new ObjectInclusionExportConfig(true)),
            new ProjectExportResource(5, "Example Group 2", new ObjectInclusionExportConfig(true)),
            new ProjectExportResource(7, "Example Group 3", new ObjectInclusionExportConfig(true))
        ], $exportableResources);

    }

    public function testExporterReturnsProjectExportForConfiguration() {

        $this->notificationService->returnValue("listNotificationGroups", [
            new NotificationGroupSummary("Notification Group 1", [new NotificationGroupMember(new UserCommunicationData(null, "Mark Robertshaw", "mark@test.com"))], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 3),
            new NotificationGroupSummary("Notification Group 2", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 5),
            new NotificationGroupSummary("Notification Group 3", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 7)
        ], [
            PHP_INT_MAX, 0, "testProject", 5
        ]);
        $exporterConfig = [3 => new ObjectInclusionExportConfig(true), 5 => new ObjectInclusionExportConfig(true)];
        $exportObjects = $this->importerExporter->createExportObjects(5, "testProject", $exporterConfig);

        $this->assertEquals([
            new NotificationGroup(new NotificationGroupSummary("Notification Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, -1), null, null),
            new NotificationGroup(new NotificationGroupSummary("Notification Group 2", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, -2), null, null)
        ], $exportObjects);


    }


    public function testCanAnalyseImportFromProjectExportWhereNoNotificationGroupsExist() {

        $exportObjects = [
            new NotificationGroup(new NotificationGroupSummary("Group 1", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 1), null, null),
            new NotificationGroup(new NotificationGroupSummary("Group 2", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 2), null, null)
        ];

        $exportConfig = "";

        // Assume no existent groups
        $this->notificationService->returnValue("listNotificationGroups", [], [
            PHP_INT_MAX, 0, "testProject", 5
        ]);

        $analysis = $this->importerExporter->analyseImportObjects(5, "testProject", $exportObjects, $exportConfig);
        $this->assertEquals([
                new ProjectImportResource(1, "Group 1", ProjectImportResourceStatus::Create),
                new ProjectImportResource(2, "Group 2", ProjectImportResourceStatus::Create),
            ]
            , $analysis);


    }


    public function testCanAnalyseImportFromProjectExportWhereExistingNotificationGroupExist() {

        $exportObjects = [
            new NotificationGroup(new NotificationGroupSummary("Group 1", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 1), null, null),
            new NotificationGroup(new NotificationGroupSummary("Group 2", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 2), null, null)
        ];

        $exportConfig = "";

        // Assume existent group
        $this->notificationService->returnValue("listNotificationGroups", [
            new NotificationGroupSummary("Group 1", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 5)
        ], [
            PHP_INT_MAX, 0, "testProject", 5
        ]);

        $analysis = $this->importerExporter->analyseImportObjects(5, "testProject", $exportObjects, $exportConfig);
        $this->assertEquals([
            new ProjectImportResource(1, "Group 1", ProjectImportResourceStatus::Ignore),
            new ProjectImportResource(2, "Group 2", ProjectImportResourceStatus::Create),
        ], $analysis);


    }


    public function testCanImportNotificationGroupsFromProjectExportWhereNoneExists() {

        $exportObjects = [
            new NotificationGroup(new NotificationGroupSummary("Group 1", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 1), null, null),
            new NotificationGroup(new NotificationGroupSummary("Group 2", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 2), null, null)
        ];

        $exportConfig = [];

        // Assume no existent groups
        $this->notificationService->returnValue("listNotificationGroups", [], [
            PHP_INT_MAX, 0, "testProject", 5
        ]);

        $this->importerExporter->importObjects(5, "testProject", $exportObjects, $exportConfig);


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


        $exportObjects = [
            new NotificationGroup(new NotificationGroupSummary("Group 1", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 1), null, null),
            new NotificationGroup(new NotificationGroupSummary("Group 2", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 2), null, null)
        ];

        $exportConfig = [];


        // Assume an existent group
        $this->notificationService->returnValue("listNotificationGroups", [
            new NotificationGroupSummary("Group 1", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 5)
        ], [
            PHP_INT_MAX, 0, "testProject", 5
        ]);

        $this->importerExporter->importObjects(5, "testProject", $exportObjects, $exportConfig);


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