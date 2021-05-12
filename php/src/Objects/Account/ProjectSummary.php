<?php


namespace Kiniauth\Objects\Account;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_project
 * @readOnly
 */
class ProjectSummary extends ActiveRecord {

    /**
     * @var int
     */
    protected $number;

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
     * @param int $number
     */
    public function __construct($name, $description = null, $number = null) {
        $this->name = $name;
        $this->description = $description;
        $this->number = $number;
    }

    /**
     * @return int
     */
    public function getNumber() {
        return $this->number;
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