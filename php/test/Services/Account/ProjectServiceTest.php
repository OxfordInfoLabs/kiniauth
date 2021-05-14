<?php


namespace Kiniauth\Services\Account;


use Kiniauth\Objects\Account\ProjectSummary;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

include_once __DIR__ . "/../../autoloader.php";

class ProjectServiceTest extends TestBase {

    /**
     * @var ProjectService
     */
    private $service;


    public function setUp(): void {
        $this->service = Container::instance()->get(ProjectService::class);
    }


    public function testCanListProjectsForLoggedInUser() {
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->assertEquals([], $this->service->listProjects());

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");
        $this->assertEquals([
            new ProjectSummary("Pressure Washing", "Pressure washing project", "pressureWashing"),
            new ProjectSummary("Soap Suds", "Soap suds project", "soapSuds"),
            new ProjectSummary("Wiper Blades", "Wiper blades project", "wiperBlades"),
        ], $this->service->listProjects());


    }

    public function testCanSaveRetrieveAndRemoveProjectForLoggedInUser() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $project = new ProjectSummary("My New Project", "A new project for testing purposes");

        // Save the project summary
        $projectKey = $this->service->saveProject($project);

        $this->assertEquals("myNewProject", $projectKey);

        $reProject = $this->service->getProject($projectKey);
        $this->assertEquals(new ProjectSummary("My New Project", "A new project for testing purposes", $projectKey), $reProject);

        // Update the project
        $reProject->setName("Updated Project Name", "Updated Project Description");
        $this->service->saveProject($reProject);

        $reReProject = $this->service->getProject($projectKey);
        $this->assertEquals($reProject, $reReProject);

        // Remove the project
        $this->service->removeProject($projectKey);

        try {
            $this->service->getProject($projectKey);
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            $this->assertTrue(true);
        }


    }


    public function testUniqueKeyCreatedIfTwoProjectsWithSameNameCreated() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $project = new ProjectSummary("Duplicate Project", "A new project for testing purposes");

        // Save the project summary
        $projectKey = $this->service->saveProject($project);
        $this->assertEquals("duplicateProject", $projectKey);

        // Save the project summary
        $projectKey = $this->service->saveProject($project);
        $this->assertEquals("duplicateProject2", $projectKey);
    }


    public function testCanListProjectsForExplicitAccount() {

        AuthenticationHelper::login("admin@kinicart.com", "password");
        $this->assertEquals([
            new ProjectSummary("Pressure Washing", "Pressure washing project", "pressureWashing"),
            new ProjectSummary("Soap Suds", "Soap suds project", "soapSuds"),
            new ProjectSummary("Wiper Blades", "Wiper blades project", "wiperBlades"),
        ], $this->service->listProjects(2));


    }

    public function testCanSaveRetrieveAndRemoveProjectForExplicitAccount() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $project = new ProjectSummary("My New Project", "A new project for testing purposes");

        // Save the project summary
        $projectKey = $this->service->saveProject($project, 2);

        $this->assertEquals("myNewProject", $projectKey);

        $reProject = $this->service->getProject($projectKey, 2);
        $this->assertEquals(new ProjectSummary("My New Project", "A new project for testing purposes", $projectKey), $reProject);

        // Update the project
        $reProject->setName("Updated Project Name", "Updated Project Description");
        $this->service->saveProject($reProject, 2);

        $reReProject = $this->service->getProject($projectKey, 2);
        $this->assertEquals($reProject, $reReProject);

        // Remove the project
        $this->service->removeProject($projectKey, 2);

        try {
            $this->service->getProject($projectKey);
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            $this->assertTrue(true);
        }


    }


    public function testCanGetMultipleProjectsByKey() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $projects = $this->service->getMultipleProjects([
            "soapSuds",
            "pressureWashing"
        ], 2);

        $this->assertEquals([
            new ProjectSummary("Soap Suds", "Soap suds project", "soapSuds"),
            new ProjectSummary("Pressure Washing", "Pressure washing project", "pressureWashing"),
        ], $projects);


    }


    public function testCanFilterProjectsUsingStringAndOffsetAndLimits() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        // Open search
        $this->assertEquals([
            new ProjectSummary("Pressure Washing", "Pressure washing project", "pressureWashing"),
            new ProjectSummary("Soap Suds", "Soap suds project", "soapSuds"),
            new ProjectSummary("Wiper Blades", "Wiper blades project", "wiperBlades"),
        ], $this->service->filterProjects("", 0, 10, 2));

        // Filter
        $this->assertEquals([
            new ProjectSummary("Pressure Washing", "Pressure washing project", "pressureWashing"),
            new ProjectSummary("Wiper Blades", "Wiper blades project", "wiperBlades"),
        ], $this->service->filterProjects("e", 0, 10, 2));


        // Limit
        $this->assertEquals([
            new ProjectSummary("Pressure Washing", "Pressure washing project", "pressureWashing"),
            new ProjectSummary("Soap Suds", "Soap suds project", "soapSuds"),
        ], $this->service->filterProjects("", 0, 2, 2));

        // Offset
        $this->assertEquals([
            new ProjectSummary("Soap Suds", "Soap suds project", "soapSuds"),
            new ProjectSummary("Wiper Blades", "Wiper blades project", "wiperBlades"),
        ], $this->service->filterProjects("", 1, 10, 2));

    }


}