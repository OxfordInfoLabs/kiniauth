<?php


namespace Kiniauth\Objects\Communication\Notification;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class NotificationGroupSummary
 * @package Kiniauth\Objects\Communication\Notification
 *
 * @table ka_notification_group
 */
class NotificationGroupSummary extends ActiveRecord {

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     * @required
     */
    protected $name;

    /**
     * @var NotificationGroupMember[]
     * @oneToMany
     * @childJoinColumns notification_group_id
     */
    protected $members;


    /**
     * Communication method - defaults to internal only
     *
     * @var string
     * @required
     */
    protected $communicationMethod = self::COMMUNICATION_METHOD_INTERNAL_ONLY;


    const COMMUNICATION_METHOD_INTERNAL_ONLY = "internal";
    const COMMUNICATION_METHOD_EMAIL = "email";

    /**
     * NotificationGroupSummary constructor.
     * @param string $name
     * @param NotificationGroupMember[] $members
     * @param string $communicationMethod
     */
    public function __construct($name, $members = [], $communicationMethod = self::COMMUNICATION_METHOD_INTERNAL_ONLY, $id = null) {
        $this->name = $name;
        $this->members = $members;
        $this->communicationMethod = $communicationMethod;
        $this->id = $id;
    }


    /**
     * @return string
     */
    public function getCommunicationMethod() {
        return $this->communicationMethod;
    }

    /**
     * @param NotificationGroupMember[] $members
     */
    public function setMembers($members) {
        $this->members = $members;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return NotificationGroupMember[]
     */
    public function getMembers() {
        return $this->members;
    }

    /**
     * @param string $communicationMethod
     */
    public function setCommunicationMethod($communicationMethod) {
        $this->communicationMethod = $communicationMethod;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }


    /**
     * @param $id
     * @return void
     */
    public function setId($id) {
        $this->id = $id;
    }
}