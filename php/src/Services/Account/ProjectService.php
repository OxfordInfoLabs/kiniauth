<?php


namespace Kiniauth\Services\Account;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\Project;
use Kiniauth\Objects\Account\ProjectSummary;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

/**
 * Project service.  Allows for creation and retrieval of projects
 *
 */
class ProjectService {


    /**
     * List projects for the supplied account id
     *
     * @param integer $accountId
     * @return ProjectSummary[]
     */
    public function listProjects($accountId = Account::LOGGED_IN_ACCOUNT) {
        $projects = Project::filter("WHERE account_id = ? ORDER BY name", $accountId);
        return array_map(function ($project) {
            return new ProjectSummary($project->getName(), $project->getDescription(), $project->getNumber());
        }, $projects);
    }

    /**
     * Get a project by id - return a project summary object
     *
     * @param integer $projectId
     * @param integer $accountId
     *
     * @return ProjectSummary
     */
    public function getProject($projectNumber, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $project = $this->getFullProject($accountId, $projectNumber);
        return new ProjectSummary($project->getName(), $project->getDescription(), $project->getNumber());
    }


    /**
     * Save a project using the passed summary object
     *
     * @param ProjectSummary $projectSummary
     */
    public function saveProject($projectSummary, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $projectId = null;
        try {
            $project = $this->getFullProject($accountId, $projectSummary->getNumber());
            $projectId = $project->getId();
        } catch (ObjectNotFoundException $e) {
        }

        $project = new Project($projectSummary->getName(), $accountId, $projectSummary->getNumber(),
            $projectSummary->getDescription(), $projectId);

        $project->save();

        return $project->getNumber();

    }


    /**
     * Remove a project by number and account
     *
     * @param $projectNumber
     * @param integer $accountId
     */
    public function removeProject($projectNumber, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $project = $this->getFullProject($accountId, $projectNumber);
        $project->remove();
    }


    /**
     * Get a full project using passed account id and project number
     *
     * @param $accountId
     * @param $projectNumber
     * @return Project
     *
     * @throws ObjectNotFoundException
     */
    private function getFullProject($accountId, $projectNumber) {
        $matches = Project::filter("WHERE account_id = ? AND number = ?", $accountId, $projectNumber);
        if (sizeof($matches) > 0) {
            return $matches[0];
        } else {
            throw new ObjectNotFoundException(Project::class, [$accountId, $projectNumber]);
        }
    }

}