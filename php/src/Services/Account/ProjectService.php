<?php


namespace Kiniauth\Services\Account;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\Project;
use Kiniauth\Objects\Account\ProjectSummary;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

/**
 * Project service.  Allows for creation and retrieval of projects
 *
 */
class ProjectService {


    /**
     * Get a project by id - return a project summary object
     *
     * @param integer $projectId
     * @param integer $accountId
     *
     * @return ProjectSummary
     */
    public function getProject($projectKey, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $project = $this->getFullProject($accountId, $projectKey);
        return new ProjectSummary($project->getName(), $project->getDescription(), $project->getProjectKey(), $project->getSettings());
    }


    /**
     * Get multiple projects by key
     *
     * @param string[] $projectKeys
     * @param string $accountId
     */
    public function getMultipleProjects($projectKeys, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $values = array_merge([$accountId], $projectKeys);
        $results = Project::filter("WHERE account_id = ? AND project_key IN (?" . str_repeat(",?", sizeof($projectKeys) - 1) . ")",
            $values);

        $projectSummaries = array_map(function ($project) {
            return new ProjectSummary($project->getName(), $project->getDescription(), $project->getProjectKey(), $project->getSettings());
        }, $results);

        return ObjectArrayUtils::indexArrayOfObjectsByMember("projectKey", $projectSummaries);
    }

    /**
     * List projects for the supplied account id
     *
     * @param integer $accountId
     * @return ProjectSummary[]
     */
    public function listProjects($accountId = Account::LOGGED_IN_ACCOUNT) {
        $projects = Project::filter("WHERE account_id = ? ORDER BY name", $accountId);
        return array_map(function ($project) {
            return new ProjectSummary($project->getName(), $project->getDescription(), $project->getProjectKey(), $project->getSettings());
        }, $projects);
    }


    /**
     * Filter projects for the logged in account
     *
     * @param $filterString
     * @param int $offset
     * @param int $limit
     * @param string $accountId
     */
    public function filterProjects($filterString = "", $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $projects = Project::filter("WHERE account_id = ? AND name LIKE ? ORDER BY name LIMIT $limit OFFSET $offset", $accountId,
            "%$filterString%");
        return array_map(function ($project) {
            return new ProjectSummary($project->getName(), $project->getDescription(), $project->getProjectKey(), $project->getSettings());
        }, $projects);
    }


    /**
     * Save a project using the passed summary object
     *
     * @param ProjectSummary $projectSummary
     */
    public function saveProject($projectSummary, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $projectId = null;
        try {
            $project = $this->getFullProject($accountId, $projectSummary->getProjectKey());
            $projectId = $project->getId();
        } catch (ObjectNotFoundException $e) {
        }

        $project = new Project($projectSummary->getName(), $accountId, $projectSummary->getProjectKey(),
            $projectSummary->getDescription(), $projectSummary->getSettings(), $projectId);

        $project->save();

        return $project->getProjectKey();

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
        $matches = Project::filter("WHERE account_id = ? AND project_key = ?", $accountId, $projectNumber);
        if (sizeof($matches) > 0) {
            return $matches[0];
        } else {
            throw new ObjectNotFoundException(Project::class, [$accountId, $projectNumber]);
        }
    }

}
