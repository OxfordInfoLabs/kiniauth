<?php


namespace Kiniauth\Objects\Security;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class UserSummary
 * @package Kiniauth\Objects\Security
 *
 * @table ka_user
 */
class UserSummary extends Securable {


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


    const STATUS_PENDING = "PENDING";
    const STATUS_ACTIVE = "ACTIVE";
    const STATUS_SUSPENDED = "SUSPENDED";
    const STATUS_LOCKED = "LOCKED";

    /**
     * Status for this user.
     *
     * @var string
     * @maxLength 30
     */
    protected $status = self::STATUS_PENDING;

    /**
     * Email address (identifies the user within the system).
     *
     * @var string
     * @required
     * @email
     * @maxLength 200
     */
    protected $emailAddress;


    /**
     * An array of explicit role objects
     *
     * @oneToMany
     * @childJoinColumns user_id
     * @var UserRole[]
     */
    protected $roles = array();


    /**
     * @var integer
     */
    protected $successfulLogins = 0;


    /**
     * @var mixed
     * @json
     * @sqlType LONGTEXT
     */
    protected $applicationSettings = [];


    /**
     * UserSummary constructor.
     * @param string $name
     * @param string $status
     */
    public function __construct($name = null, $status = null, $emailAddress = null, $successfulLogins = 0, $applicationSettings = []) {
        $this->name = $name;
        $this->status = $status;
        $this->emailAddress = $emailAddress;
        $this->successfulLogins = $successfulLogins;
        $this->applicationSettings = $applicationSettings;
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


    /**
     * @return int
     */
    public function getSuccessfulLogins() {
        return $this->successfulLogins;
    }

    /**
     * @return mixed
     */
    public function getApplicationSettings() {
        return $this->applicationSettings ?? [];
    }


    /**
     * @return UserRole[]
     */
    public function getRoles() {
        return $this->roles;
    }

    /**
     * @param UserRole[] $roles
     */
    public function setRoles($roles) {
        $this->roles = $roles;
    }


    /**
     * @return null
     */
    public function getActiveAccountId() {
        return null;
    }

}
