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
     * @http GET /
     */
    public function filterProjects($filterString = 0, $limit = 10, $offset = 0) {
        return $this->projectService->filterProjects($filterString, $offset, $limit);
    }


    /**
     * @http GET /$projectKey
     *
     * @param $projectKey
     */
    public function getProject($projectKey) {
        return $this->projectService->getProject($projectKey);
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
     * @param $projectUpdateDescriptor
     */
    public function updateProject($projectKey, $projectUpdateDescriptor) {
        $projectSummary = new ProjectSummary($projectUpdateDescriptor->getName(), $projectUpdateDescriptor->getDescription(), $projectKey);
        $this->projectService->saveProject($projectSummary);
    }


    /**
     * Remove a project by key
     *
     * @param $projectKey
     */
    public function removeProject($projectKey) {
        $this->projectService->removeProject($projectKey);
    }

}