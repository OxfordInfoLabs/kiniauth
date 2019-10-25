<?php


namespace Kiniauth\Objects\Security;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class UserSummary
 * @package Kiniauth\Objects\Security
 * @noGenerate
 */
class UserSummary extends ActiveRecord {

    const STATUS_PENDING = "PENDING";
    const STATUS_ACTIVE = "ACTIVE";
    const STATUS_SUSPENDED = "SUSPENDED";
    const STATUS_PASSWORD_RESET = "PASSWORD_RESET";

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
     * UserSummary constructor.
     * @param null $name
     * @param null $status
     */
    public function __construct($name = null, $status = null) {
        $this->name = $name;
        $this->status = $status;
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



}
