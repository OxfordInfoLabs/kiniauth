<?php

namespace Kiniauth\Test\Services\Security;

use Kiniauth\Traits\Application\Timestamped;
use Kinikit\Persistence\ORM\ActiveRecord;

class TestTimestampObject extends ActiveRecord {

    use Timestamped;

    /**
     * @var integer
     * @primaryKey
     */
    private $id;


    /**
     * @var string
     */
    private $name;

    /**
     * @param int $id
     * @param string $name
     */
    public function __construct($id, $name, $createdDate = null, $lastModifiedDate = null) {
        $this->id = $id;
        $this->name = $name;
        $this->createdDate = $createdDate;
        $this->lastModifiedDate = $lastModifiedDate;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
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


}