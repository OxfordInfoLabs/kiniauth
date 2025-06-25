<?php

namespace Kiniauth\Test\Services\ImportExport\ImportExporters;

use Kiniauth\Controllers\Account\KeyPair;
use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Objects\Security\APIKeyRole;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\ImportExport\ImportExporters\KeyPairImportExporter;
use Kiniauth\Services\Security\KeyPairService;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ExportObjects\ExportedApiKey;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kiniauth\ValueObjects\Util\LabelValue;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use PhpParser\Node\Stmt\Label;

include_once "autoloader.php";

/**
 *
 */
class KeyPairImportExporterTest extends TestBase {

    /**
     * @var KeyPairImportExporter
     */
    private $importExporter;

    /**
     * @var KeyPairService|MockObject
     */
    private $keyPairService;


    public function setUp(): void {

        // Mock key pair service
        $this->keyPairService = MockObjectProvider::mock(KeyPairService::class);
        $this->importExporter = new KeyPairImportExporter($this->keyPairService);
    }


    public function testCanGetExportableResources() {

        $keyPairList = [
            new LabelValue("Tom's Key", 55),
            new LabelValue("Andrew's Key", 66)
        ];


        $this->keyPairService->returnValue("listKeyPairs", $keyPairList, [
            "testProj",
            33
        ]);

        $exportableResources = $this->importExporter->getExportableProjectResources(33, "testProj");

        $this->assertEquals([
            new ProjectExportResource(55, "Tom's Key", new ObjectInclusionExportConfig(true)),
            new ProjectExportResource(66, "Andrew's Key", new ObjectInclusionExportConfig(true)),
        ], $exportableResources);

    }


    public function testCanCreateExportObjects() {

        $keyPairList = [
            new LabelValue("Tom's Key", 55),
            new LabelValue("Andrew's Key", 66),
            new LabelValue("Bob's Key", 77)
        ];


        $this->keyPairService->returnValue("listKeyPairs", $keyPairList, [
            "testProj",
            33
        ]);

        $exportObjects = $this->importExporter->createExportObjects(33, "testProj", [
            55 => new ObjectInclusionExportConfig(true),
            66 => new ObjectInclusionExportConfig(false),
            77 => new ObjectInclusionExportConfig(true)
        ], []);

        $this->assertEquals([
            new LabelValue("Tom's Key", -1),
            new LabelValue("Bob's Key", -2)
        ], $exportObjects);

    }


    public function testCanAnalyseImportObjects() {

        $this->keyPairService->returnValue("listKeyPairs", [
            new LabelValue("Test Key 1", 25)
        ], ["testProject", 5]);

        $this->assertEquals([
            new ProjectImportResource(-1, "Test Key 1", ProjectImportResourceStatus::Update, 25),
            new ProjectImportResource(-2, "Test Key 3", ProjectImportResourceStatus::Create)
        ], $this->importExporter->analyseImportObjects(5, "testProject", [
            new LabelValue("Test Key 1", -1),
            new LabelValue("Test Key 3", -2,)
        ], [-1 => new ObjectInclusionExportConfig(true),
            -2 => new ObjectInclusionExportConfig(true)]));

    }

    public function testCanImportAndCreateOrUpdateKeyPairsIntoTargetAccountFromExport() {

        $this->keyPairService->returnValue("listKeyPairs", [
            new LabelValue("Test Key 1", 55)
        ], ["testProject", 5]);


        $this->importExporter->importObjects(5, "testProject", [
            new LabelValue("Test Key 1", -1,),
            new LabelValue("Test Key 3", -2)
        ], [-1 => new ObjectInclusionExportConfig(true),
            -2 => new ObjectInclusionExportConfig(true)]);


        $this->assertFalse($this->keyPairService->methodWasCalled("generateKeyPair", [
            "Test Key 1", "testProject", 5
        ]));

        $this->assertTrue($this->keyPairService->methodWasCalled("generateKeyPair", [
            "Test Key 3", "testProject", 5
        ]));
    }

}