<?php


namespace Kiniauth\Objects\Security;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class UserLabel
 * @package Kiniauth\Objects\Security
 *
 * @table ka_user
 */
class UserLabel extends ActiveRecord {


    /**
     * Auto incremented id.
     *
     * @var integer
     */
    protected $id;

    /**
     * The full name for this user.  May or may not be required depending on the application.
     *
     * @maxLength 100
     * @var string
     */
    protected $name;

    /**
     * UserLabel constructor.
     *
     * @param int $id
     * @param string $name
     */
    public function __construct($id = null, $name = null) {
        $this->id = $id;
        $this->name = $name;
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }
}