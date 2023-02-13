<?php


namespace Kiniauth\Objects\Security;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Historical record of previous hashed passwords used by this user.
 *
 * Class UserHashedPasswords
 *
 * @table ka_user_password_history
 * @generate
 */
class UserPasswordHistory extends ActiveRecord {

    /**
     * @var int
     * @primaryKey
     */
    private $userId;


    /**
     * @var string
     * @primaryKey
     */
    private $hashedPassword;

    /**
     * UserHashedPasswords constructor.
     * @param int $userId
     * @param string $hashedPassword
     */
    public function __construct($userId, $hashedPassword) {
        $this->userId = $userId;
        $this->hashedPassword = $hashedPassword;
    }


}