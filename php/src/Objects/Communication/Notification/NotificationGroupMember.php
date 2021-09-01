<?php


namespace Kiniauth\Objects\Communication\Notification;

use Kiniauth\Objects\Security\UserCommunicationData;

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
     * @var UserCommunicationData
     * @manyToOne
     * @parentJoinColumns user_id
     * @requiredEither memberData
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
     * @param UserCommunicationData $user
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
     * @return UserCommunicationData
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param UserCommunicationData $user
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
