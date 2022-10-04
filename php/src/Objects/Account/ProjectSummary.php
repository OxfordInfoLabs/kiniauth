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
     * @var mixed
     * @sqlType longtext
     * @json
     */
    protected $settings;

    /**
     * Project summary constructor.
     *
     * @param string $name
     * @param string $description
     * @param string $key
     * @param string $settings
     */
    public function __construct($name, $description = null, $key = null, $settings = null) {
        $this->name = $name;
        $this->description = $description;
        $this->projectKey = $key;
        $this->settings = $settings ?? [];
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

    /**
     * @return mixed
     */
    public function getSettings() {
        return $this->settings;
    }

    /**
     * @param mixed $settings
     */
    public function setSettings($settings) {
        $this->settings = $settings;
    }


}
