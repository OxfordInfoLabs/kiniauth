<?php

namespace Kiniauth\Test\Services\ImportExport;

use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupMember;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Security\UserCommunicationData;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\Services\ImportExport\DefaultProjectExporter;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kiniauth\ValueObjects\ImportExport\ProjectExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;

include_once "autoloader.php";

class DefaultProjectExporterTest extends TestBase {

    /**
     * @var DefaultProjectExporter
     */
    private $exporter;

    /**
     * @var MockObject|NotificationService
     */
    private $notificationService;


    public function setUp(): void {
        $this->notificationService = MockObjectProvider::mock(NotificationService::class);
        $this->exporter = new DefaultProjectExporter($this->notificationService);
    }


    public function testExporterReturnsNotificationGroupsCorrectlyForExportableProjectResources() {
        $this->notificationService->returnValue("listNotificationGroups", [
            new NotificationGroupSummary("Example Group 1", [], [], 3),
            new NotificationGroupSummary("Example Group 2", [], [], 5),
            new NotificationGroupSummary("Example Group 3", [], [], 7)
        ], [
            PHP_INT_MAX, 0, "myProject", 7
        ]);

        $exportableResources = $this->exporter->getExportableProjectResources(7, "myProject");

        $this->assertEquals([
            "notificationGroups" => [
                new ProjectExportResource(3, "Example Group 1"),
                new ProjectExportResource(5, "Example Group 2"),
                new ProjectExportResource(7, "Example Group 3")
            ]
        ], $exportableResources->getResourcesByType());

    }

    public function testExporterReturnsProjectExportForConfiguration() {

        $this->notificationService->returnValue("listNotificationGroups", [
            new NotificationGroupSummary("Notification Group 1", [new NotificationGroupMember(new UserCommunicationData(null, "Mark Robertshaw", "mark@test.com"))], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 3),
            new NotificationGroupSummary("Notification Group 2", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 5),
            new NotificationGroupSummary("Notification Group 3", [], NotificationGroup::COMMUNICATION_METHOD_EMAIL, 7)
        ], [
            PHP_INT_MAX, 0, "testProject", 5
        ]);
        $exporterConfig = new ProjectExportConfig([3, 5]);
        $projectExport = $this->exporter->exportProject(5, "testProject", $exporterConfig);

        $this->assertEquals(new ProjectExport([
            new NotificationGroup(new NotificationGroupSummary("Notification Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, -1), null, null),
            new NotificationGroup(new NotificationGroupSummary("Notification Group 2", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, -2), null, null),

        ]),
            $projectExport);


    }

}