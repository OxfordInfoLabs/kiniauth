<?php


namespace Kiniauth\Objects\Security;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class UserLabel
 * @package Kiniauth\Objects\Security
 *
 * @table ka_user
 */
class UserCommunicationData extends ActiveRecord {


    /**
     * Auto incremented id.
     *
     * @var integer
     */
    private $id;

    /**
     * The full name for this user.  May or may not be required depending on the application.
     *
     * @var string
     */
    private $name;

    /**
     * The email address for the user
     *
     * @var string
     */
    private $emailAddress;

    /**
     * The mobile number for the user
     *
     * @var string
     */
    private $mobileNumber;


    /**
     * UserLabel constructor.
     *
     * @param int $id
     * @param string $name
     */
    public function __construct($id = null, $name = null, $emailAddress = null, $mobileNumber = null) {
        $this->id = $id;
        $this->name = $name;
        $this->emailAddress = $emailAddress;
        $this->mobileNumber = $mobileNumber;
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

    /**
     * @return string
     */
    public function getEmailAddress() {
        return $this->emailAddress;
    }

    /**
     * @return string
     */
    public function getMobileNumber() {
        return $this->mobileNumber;
    }


}