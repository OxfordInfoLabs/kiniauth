<?php


namespace Kiniauth\Objects\Security;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class UserSession
 *
 * @table ka_user_session
 * @generate
 */
class UserSession extends ActiveRecord {

    /**
     * @var integer
     * @primaryKey
     */
    private $userId;

    /**
     * @var string
     * @maxLength 128
     * @primaryKey
     */
    private $sessionId;

    /**
     * @var \DateTime
     */
    private $createdDateTime;


    /**
     * @var UserSessionProfile
     * @manyToOne
     * @parentJoinColumns user_id, profile_hash
     * @saveCascade
     */
    private $profile;


    /**
     * UserSession constructor.
     *
     * @param integer $userId
     * @param string $sessionId
     * @param UserSessionProfile $profile
     */
    public function __construct($userId, $sessionId, $profile) {
        $this->userId = $userId;
        $this->sessionId = $sessionId;
        $this->profile = $profile;
        $this->createdDateTime = new \DateTime();
    }


    /**
     * @return int
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId) {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getSessionId() {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     */
    public function setSessionId($sessionId) {
        $this->sessionId = $sessionId;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedDateTime() {
        return $this->createdDateTime;
    }

    /**
     * @param \DateTime $createdDateTime
     */
    public function setCreatedDateTime($createdDateTime) {
        $this->createdDateTime = $createdDateTime;
    }

    /**
     * @return UserSessionProfile
     */
    public function getProfile() {
        return $this->profile;
    }

    /**
     * @param UserSessionProfile $profile
     */
    public function setProfile($profile) {
        $this->profile = $profile;
    }


}
