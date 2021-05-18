<?php


namespace Kiniauth\Objects\Account;


/**
 * @table ka_project
 * @generate
 * @interceptor \Kiniauth\Objects\Account\ProjectInterceptor
 */
class Project extends ProjectSummary {
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     * @required
     */
    private $accountId;


    /**
     * Project constructor.
     * @param int $accountId
     * @param string $name
     * @param string $description
     */
    public function __construct($name, $accountId, $projectProjectKey = null, $description = null, $id = null) {
        parent::__construct($name, $description, $projectProjectKey);
        $this->accountId = $accountId;
        $this->id = $id;
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }


    /**
     * @return int
     */
    public function getAccountId() {
        return $this->accountId;
    }

    /**
     * @param int $accountId
     */
    public function setAccountId($accountId) {
        $this->accountId = $accountId;
    }

    /**
     * @param string $projectKey
     */
    public function setProjectKey($projectKey) {
        $this->projectKey = $projectKey;
    }


}