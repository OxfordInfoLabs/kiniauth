<?php


namespace Kiniauth\Objects\Security;

use Kinikit\Core\Util\StringUtils;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * User access token
 *
 * @table ka_user_access_token
 * @generate
 */
class UserAccessToken extends ActiveRecord {

    /**
     * The user id this token relates to
     *
     * @var integer
     * @primaryKey
     */
    private $userId;


    /**
     * @var string
     * @primaryKey
     */
    private $tokenHash;


    /**
     * Create a new user access token for a user
     *
     * UserAccessToken constructor.
     */
    public function __construct($userId, $token) {
        $this->userId = $userId;
        $this->tokenHash = md5($token);
    }

    /**
     * @return int
     */
    public function getUserId() {
        return $this->userId;
    }


    /**
     * @param string $tokenHash
     */
    public function setTokenHash($tokenHash) {
        $this->tokenHash = $tokenHash;
    }


}
