<?php

namespace Kiniauth\Test\Services\Security;

use Kiniauth\Objects\Workflow\PropertyChangeWorkflow;

class ExamplePropertyChangeObject implements PropertyChangeWorkflow {

    /**
     * @var integer
     * @primaryKey
     */
    private $id;


    /**
     * @var string
     */
    private $status;

    /**
     * @param int $id
     * @param string $status
     */
    public function __construct($id, $status) {
        $this->id = $id;
        $this->status = $status;
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
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status) {
        $this->status = $status;
    }


}