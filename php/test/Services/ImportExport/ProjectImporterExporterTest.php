<?php

namespace Kiniauth\Test\Services\ImportExport;

use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Services\ImportExport\ImportExporters\NotificationGroupImportExporter;
use Kiniauth\Services\ImportExport\ProjectImporterExporter;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\ImportExport\ExportableProjectResources;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportAnalysis;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\MockObject\MockObject;

include_once "autoloader.php";

class ProjectImporterExporterTest extends TestBase {


    private ProjectImporterExporter $projectImportedExporter;

    private MockObject|NotificationGroupImportExporter $importExporter;

    public function setUp(): void {
        $this->importExporter = MockObjectProvider::mock(NotificationGroupImportExporter::class);
        $this->importExporter->returnValue("getObjectTypeCollectionIdentifier", "notificationGroups");
        $this->importExporter->returnValue("getObjectTypeCollectionTitle", "Notification Groups");
        $this->importExporter->returnValue("getObjectTypeImportClassName", NotificationGroup::class);
        $this->importExporter->returnValue("getObjectTypeExportConfigClassName", ObjectInclusionExportConfig::class);

        $this->projectImportedExporter = new ProjectImporterExporter($this->importExporter, Container::instance()->get(ObjectBinder::class));
    }


    public function testGetExportableProjectResourcesCallsImportExportersCorrectly() {

        $expectedResources = [
            new ProjectExportResource(1, "Test 1", new ObjectInclusionExportConfig(true)),
            new ProjectExportResource(2, "Test 2", new ObjectInclusionExportConfig(true))
        ];

        $this->importExporter->returnValue("getExportableProjectResources", $expectedResources, [5, "hello"]);

        $resources = $this->projectImportedExporter->getExportableProjectResources(5, "hello");

        $this->assertEquals(new ExportableProjectResources([
            "notificationGroups" => $expectedResources
        ]), $resources);
    }

    public function testCreateExportObjectsCallsImportExportersCorrectly() {

        $expectedObjects = [
            "Bingo",
            "Bongo"
        ];

        $this->importExporter->returnValue("createExportObjects", $expectedObjects, [5, "hello", [
            1 => new ObjectInclusionExportConfig(true)
        ], ["notificationGroups" => [
            1 => new ObjectInclusionExportConfig(true)
        ]]]);

        $export = $this->projectImportedExporter->exportProject(5, "hello", ["notificationGroups" => [
            1 => ["included" => true]
        ]]);

        $this->assertEquals(new ProjectExport(["notificationGroups" => $expectedObjects], ["notificationGroups" => [
            1 => new ObjectInclusionExportConfig(true)
        ]]), $export);


    }

    public function testAnalyseImportCreatesProjectImportAnalysisUsingImportExportersAndMapsExportObjectsToClassType() {

        $expectedResources = [
            new ProjectImportResource(1, "Hello world", ProjectImportResourceStatus::Create),
            new ProjectImportResource(2, "Hello world 2", ProjectImportResourceStatus::Ignore),
        ];

        $exportData =
            ["notificationGroups" =>
                [
                    ["id" => -1, "name" => "My Notification Group"],
                    ["id" => -2, "name" => "My Notification Group 2"]
                ]];

        $exportConfig = ["notificationGroups" => [
            1 => ["included" => true]
        ]];



        $this->importExporter->returnValue("analyseImportObjects", $expectedResources, [
                5, "hello", [
                    new NotificationGroup(new NotificationGroupSummary("My Notification Group", [], NotificationGroup::COMMUNICATION_METHOD_INTERNAL_ONLY, -1), null, null),
                    new NotificationGroup(new NotificationGroupSummary("My Notification Group 2", [], NotificationGroup::COMMUNICATION_METHOD_INTERNAL_ONLY, -2), null, null)
                ],
                [1 => new ObjectInclusionExportConfig(true)]]
        );


        $analysis = $this->projectImportedExporter->analyseImport(5, "hello", new ProjectExport($exportData, $exportConfig));


        $this->assertEquals(new ProjectImportAnalysis(date("Y-m-d H:i:s"), [
            "Notification Groups" => $expectedResources
        ]), $analysis);

    }


    public function testImportCallsImportUsingImportExportersAndMapsExportObjectsToClass() {


        $exportData =
            ["notificationGroups" =>
                [
                    ["id" => -1, "name" => "My Notification Group"],
                    ["id" => -2, "name" => "My Notification Group 2"]
                ]];

        $exportConfig = ["notificationGroups" => [
            1 => ["included" => true]
        ]];



        $this->projectImportedExporter->importProject(5, "hello", new ProjectExport($exportData, $exportConfig));


        $this->assertTrue($this->importExporter->methodWasCalled("importObjects", [
                5, "hello", [
                    new NotificationGroup(new NotificationGroupSummary("My Notification Group", [], NotificationGroup::COMMUNICATION_METHOD_INTERNAL_ONLY, -1), null, null),
                    new NotificationGroup(new NotificationGroupSummary("My Notification Group 2", [], NotificationGroup::COMMUNICATION_METHOD_INTERNAL_ONLY, -2), null, null)
                ],
                [1 => new ObjectInclusionExportConfig(true)]]
        ));


    }


}