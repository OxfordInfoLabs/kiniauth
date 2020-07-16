<?php


namespace Kiniauth\Test\Services\Security;


class TestNonAccountObject {

    private $id;
    private $name;
    private $notes;

    /**
     * TestNonAccountObject constructor.
     * @param $id
     * @param $name
     * @param $notes
     */
    public function __construct($id = null, $name = null, $notes = null) {
        $this->id = $id;
        $this->name = $name;
        $this->notes = $notes;
    }

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getNotes() {
        return $this->notes;
    }

    /**
     * @param mixed $notes
     */
    public function setNotes($notes) {
        $this->notes = $notes;
    }


}
