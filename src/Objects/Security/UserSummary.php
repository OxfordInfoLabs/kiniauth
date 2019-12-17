<?php


namespace Kiniauth\Objects\Security;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class UserSummary
 * @package Kiniauth\Objects\Security
 *
 * @table ka_user
 */
class UserSummary extends ActiveRecord {

    const STATUS_PENDING = "PENDING";
    const STATUS_ACTIVE = "ACTIVE";
    const STATUS_SUSPENDED = "SUSPENDED";
    const STATUS_PASSWORD_RESET = "PASSWORD_RESET";

    /**
     * Auto incremented id.
     *
     * @var integer
     */
    protected $id;

    /**
     * The full name for this user.  May or may not be required depending on the application.
     *
     * @var string
     */
    protected $name;

    /**
     * Status for this user.
     *
     * @var string
     * @maxlength(30)
     */
    protected $status = self::STATUS_PENDING;

    /**
     * Email address (identifies the user within the system).
     *
     * @var string
     * @required
     * @email
     */
    protected $emailAddress;

    /**
     * An array of explicit user account role objects
     *
     * @oneToMany
     * @childJoinColumns user_id
     * @var UserRole[]
     */
    protected $roles = array();

    /**
     * UserSummary constructor.
     * @param null $name
     * @param null $status
     */
    public function __construct($name = null, $status = null, $emailAddress = null) {
        $this->name = $name;
        $this->status = $status;
        $this->emailAddress = $emailAddress;
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
     * @param mixed $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status) {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getEmailAddress() {
        return $this->emailAddress;
    }



}
