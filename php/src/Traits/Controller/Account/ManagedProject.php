<?php

namespace Kiniauth\Traits\Controller\Account;

use Kiniauth\Services\ImportExport\ManagedProject\ManagedProjectService;

trait ManagedProject {

    private ManagedProjectService $managedProjectService;

    /**
     * @param ManagedProjectService $managedProjectService
     */
    public function __construct($managedProjectService) {
        $this->managedProjectService = $managedProjectService;
    }

    /**
     * @http GET /all
     */
    public function getAllManagedProjects() {
        return $this->managedProjectService->getAll();
    }

    /**
     * @http GET /$id
     *
     * @param int $id
     */
    public function getManagedProject(int $id) {
        return $this->managedProjectService->getManagedProject($id);
    }

    /**
     * @http POST /create
     *
     * @param string $name
     * @param int $accountId
     * @param string $projectKey
     */
    public function createManagedProject($name, $accountId, $projectKey) {
        return $this->managedProjectService->createManagedProject($name, $accountId, $projectKey);
    }

    /**
     * @http POST /search
     *
     * @param int $accountId
     * @param string $projectKey
     */
    public function searchManagedProjects($accountId, $projectKey) {
        return $this->managedProjectService->searchForManagedProjects($accountId, $projectKey);
    }

    /**
     * @http PUT /deploy/$versionId
     *
     * @param int $versionId
     */
    public function issueUpdate(int $versionId): void {
        $this->managedProjectService->issueProjectUpdate($versionId);
    }

    /**
     * @http POST /deploy/new
     *
     * @param int $managedProjectId
     */
    public function deployNew(int $managedProjectId): void {
        $this->managedProjectService->exportAndUpdate($managedProjectId);
    }

    /**
     * @http POST /install
     *
     * @param int $managedProjectId
     * @param int $targetAccountId
     */
    public function install(int $managedProjectId, int $targetAccountId) {
        $this->managedProjectService->installProjectOnAccount($managedProjectId, $targetAccountId);
    }

}