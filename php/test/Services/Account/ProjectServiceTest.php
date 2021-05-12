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
            new ProjectSummary("Pressure Washing", "Pressure washing project", 3),
            new ProjectSummary("Soap Suds", "Soap suds project", 1),
            new ProjectSummary("Wiper Blades", "Wiper blades project", 2),
        ], $this->service->listProjects());


    }

    public function testCanSaveRetrieveAndRemoveProjectForLoggedInUser() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $project = new ProjectSummary("My New Project", "A new project for testing purposes");

        // Save the project summary
        $projectNumber = $this->service->saveProject($project);

        $this->assertEquals(1, $projectNumber);

        $reProject = $this->service->getProject($projectNumber);
        $this->assertEquals(new ProjectSummary("My New Project", "A new project for testing purposes", $projectNumber), $reProject);

        // Update the project
        $reProject->setName("Updated Project Name", "Updated Project Description");
        $this->service->saveProject($reProject);

        $reReProject = $this->service->getProject($projectNumber);
        $this->assertEquals($reProject, $reReProject);

        // Remove the project
        $this->service->removeProject($projectNumber);

        try {
            $this->service->getProject($projectNumber);
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            $this->assertTrue(true);
        }


    }


    public function testCanListProjectsForExplicitAccount() {

        AuthenticationHelper::login("admin@kinicart.com", "password");
        $this->assertEquals([
            new ProjectSummary("Pressure Washing", "Pressure washing project", 3),
            new ProjectSummary("Soap Suds", "Soap suds project", 1),
            new ProjectSummary("Wiper Blades", "Wiper blades project", 2),
        ], $this->service->listProjects(2));


    }

    public function testCanSaveRetrieveAndRemoveProjectForExplicitAccount() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $project = new ProjectSummary("My New Project", "A new project for testing purposes");

        // Save the project summary
        $projectNumber = $this->service->saveProject($project, 2);

        $this->assertEquals(4, $projectNumber);

        $reProject = $this->service->getProject($projectNumber, 2);
        $this->assertEquals(new ProjectSummary("My New Project", "A new project for testing purposes", $projectNumber), $reProject);

        // Update the project
        $reProject->setName("Updated Project Name", "Updated Project Description");
        $this->service->saveProject($reProject, 2);

        $reReProject = $this->service->getProject($projectNumber, 2);
        $this->assertEquals($reProject, $reReProject);

        // Remove the project
        $this->service->removeProject($projectNumber, 2);

        try {
            $this->service->getProject($projectNumber);
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            $this->assertTrue(true);
        }


    }

}