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
    protected $key;

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
     * @param string $description \
     * @param string $key
     */
    public function __construct($name, $description = null, $key = null) {
        $this->name = $name;
        $this->description = $description;
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
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