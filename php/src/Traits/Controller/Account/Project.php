<?php


namespace Kiniauth\Traits\Controller\Account;


use Kiniauth\Objects\Account\ProjectSummary;
use Kiniauth\Services\Account\ProjectService;
use Kiniauth\ValueObjects\Account\ProjectUpdateDescriptor;

trait Project {

    /**
     * @var ProjectService
     */
    private $projectService;

    /**
     * Project constructor.
     * @param ProjectService $projectService
     */
    public function __construct($projectService) {
        $this->projectService = $projectService;
    }


    /**
     * @http GET /$projectKey
     *
     * @param $projectKey
     * @return ProjectSummary
     */
    public function getProject($projectKey) {
        return $this->projectService->getProject($projectKey);
    }


    /**
     * @http GET /
     *
     * @param string $filterString
     * @param int $limit
     * @param int $offset
     *
     * @return ProjectSummary[]
     */
    public function filterProjects($filterString = "", $limit = 10, $offset = 0) {
        return $this->projectService->filterProjects($filterString, $offset, $limit);
    }

    /**
     * @http POST /
     *
     * @param ProjectUpdateDescriptor $projectUpdateDescriptor
     */
    public function createProject($projectUpdateDescriptor) {
        $projectSummary = new ProjectSummary($projectUpdateDescriptor->getName(), $projectUpdateDescriptor->getDescription());
        $this->projectService->saveProject($projectSummary);
    }


    /**
     * Update a project using supplied key and descriptor
     *
     * @http PUT /$projectKey
     *
     * @param $projectKey
     * @param ProjectUpdateDescriptor $projectUpdateDescriptor
     */
    public function updateProject($projectKey, $projectUpdateDescriptor) {
        $projectSummary = new ProjectSummary($projectUpdateDescriptor->getName(), $projectUpdateDescriptor->getDescription(), $projectKey);
        $this->projectService->saveProject($projectSummary);
    }


    /**
     * Remove a project by key
     *
     * @http DELETE /$projectKey
     *
     * @param $projectKey
     */
    public function removeProject($projectKey) {
        $this->projectService->removeProject($projectKey);
    }

    /**
     * Update the project settings object
     *
     * @http PUT /$projectKey/settings
     *
     * @param $projectKey
     * @param mixed $settings
     * @return void
     */
    public function updateProjectSettings($projectKey, $settings) {
        $project = $this->getProject($projectKey);
        $project->setSettings($settings);
        $this->projectService->saveProject($project);
    }
}
