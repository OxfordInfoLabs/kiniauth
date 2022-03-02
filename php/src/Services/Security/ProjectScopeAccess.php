<?php


namespace Kiniauth\Services\Security;


use Kiniauth\Services\Account\ProjectService;
use Kinikit\Core\Util\ObjectArrayUtils;

class ProjectScopeAccess extends StandardAccountObjectScopeAccess {

    const SCOPE_PROJECT = "PROJECT";


    /**
     * @var ProjectService
     */
    private $projectService;

    /**
     * ProjectScopeAccess constructor.
     *
     * @param ProjectService $projectService
     */
    public function __construct($projectService) {
        parent::__construct(self::SCOPE_PROJECT, "Project", "projectKey");
        $this->projectService = $projectService;
    }


    /**
     * Get the scope object descriptions by id
     *
     * @param $scopeIds
     * @param null $accountId
     * @return mixed|void
     */
    public function getScopeObjectDescriptionsById($scopeIds, $accountId = null) {
        $projects = $this->projectService->getMultipleProjects($scopeIds, $accountId);
        return ObjectArrayUtils::getMemberValueArrayForObjects("name", $projects);
    }

    /**
     * Get filtered project descriptions
     *
     * @param string $searchFilter
     * @param int $offset
     * @param int $limit
     * @param null $accountId
     */
    public function getFilteredScopeObjectDescriptions($searchFilter, $offset = 0, $limit = 10, $accountId = null) {
        $matches = $this->projectService->filterProjects($searchFilter, $offset, $limit, $accountId);
        return ObjectArrayUtils::getMemberValueArrayForObjects("name", ObjectArrayUtils::indexArrayOfObjectsByMember("projectKey", $matches));
    }
}