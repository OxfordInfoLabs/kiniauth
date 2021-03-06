<?php


namespace Kiniauth\Objects\Account;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_project
 * @readOnly
 */
class ProjectSummary extends ActiveRecord {

    /**
     * @var string
     */
    protected $projectKey;

    /**
     * @var string
     * @required
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;


    /**
     * Project summary constructor.
     *
     * @param string $name
     * @param string $description
     * @param string $projectKey
     */
    public function __construct($name, $description = null, $projectKey = null) {
        $this->name = $name;
        $this->description = $description;
        $this->projectKey = $projectKey;
    }

    /**
     * @return string
     */
    public function getProjectKey() {
        return $this->projectKey;
    }


    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }


}