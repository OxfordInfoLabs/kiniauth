<?php


namespace Kiniauth\Objects\Security;


use Kinikit\Core\Security\Hash\SHA512HashProvider;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class UserSessionProfile
 *
 * @table ka_user_session_profile
 * @generate
 */
class UserSessionProfile extends ActiveRecord {

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
    private $profileHash;


    /**
     * @var string
     */
    private $ipAddress;


    /**
     * @var string
     */
    private $userAgent;


    /**
     * UserSessionProfile constructor.
     *
     * Construct with key fields
     *
     * @param string $ipAddress
     * @param string $userAgent
     */
    public function __construct($ipAddress, $userAgent, $userId = null) {
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->userId = $userId;

        $hashProvider = new SHA512HashProvider();
        $this->profileHash = $hashProvider->generateHash($ipAddress . $userAgent);
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
    public function getProfileHash() {
        return $this->profileHash;
    }

    /**
     * @param string $profileHash
     */
    public function setProfileHash($profileHash) {
        $this->profileHash = $profileHash;
    }

    /**
     * @return string
     */
    public function getIpAddress() {
        return $this->ipAddress;
    }

    /**
     * @param string $ipAddress
     */
    public function setIpAddress($ipAddress) {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @return string
     */
    public function getUserAgent() {
        return $this->userAgent;
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent($userAgent) {
        $this->userAgent = $userAgent;
    }


}
