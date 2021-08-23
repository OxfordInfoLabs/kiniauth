<?php


namespace Kiniauth\Objects\Communication\Notification;

use Kiniauth\Objects\Security\UserLabel;

/**
 * Class NotificationGroupMember
 * @package Kiniauth\Objects\Communication\Notification
 *
 * @table ka_notification_group_member
 * @generate
 */
class NotificationGroupMember {

    /**
     * @var integer
     */
    private $id;


    /**
     * User for this group member if using
     *
     * @var UserLabel
     * @manyToOne
     * @parentJoinColumns user_id
     */
    private $user;


    /**
     * Member data - relevant to the communication method if not using a user id
     *
     * @var string
     */
    private $memberData;

    /**
     * NotificationGroupMember constructor.
     * @param UserLabel $user
     * @param string $memberData
     */
    public function __construct($user = null, $memberData = null, $id = null) {
        $this->user = $user;
        $this->memberData = $memberData;
        $this->id = $id;
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
     * @return UserLabel
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param UserLabel $user
     */
    public function setUser($user) {
        $this->user = $user;
    }


    /**
     * @return string
     */
    public function getMemberData() {
        return $this->memberData;
    }

    /**
     * @param string $memberData
     */
    public function setMemberData($memberData) {
        $this->memberData = $memberData;
    }


}
