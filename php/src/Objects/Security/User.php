<?php


namespace Kiniauth\Objects\Security;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Application\Session;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\ValidationException;
use Kinikit\Core\Security\Hash\SHA512HashProvider;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Core\Util\StringUtils;
use Kinikit\Core\Validation\FieldValidationError;


/**
 * Main user entity for accessing the system.  Users typically belong to one or more accounts or are super users.
 *
 * @table ka_user
 * @generate
 * @interceptor \Kiniauth\Objects\Security\UserInterceptor
 */
class User extends UserSummary {


    /**
     * An optional parent account id, if this account has been created in the context of a
     * parent account.  This allows for multiple accounts for the same email address across multiple
     * contexts.
     *
     * @var integer
     */
    protected $parentAccountId = 0;


    /**
     * Hashed password for interactive login checks
     *
     * @var string
     * @minLength 64
     * @required
     */
    protected $hashedPassword;


    /**
     * Optional mobile phone for extra security checks.
     *
     * @var string
     * @regexp [0-9\+\. ]+
     * @maxLength 30
     */
    protected $mobileNumber;


    /**
     * Backup email address for extra security checks.
     *
     * @var string
     * @email
     * @maxLength 200
     */
    protected $backupEmailAddress;


    /**
     * Active account id.  This will default to the first account found for the
     * user based upon roles if not supplied.
     *
     * @var integer
     */
    protected $activeAccountId;


    /**
     * @var integer
     */
    protected $invalidLoginAttempts = 0;


    /**
     * @var \DateTime
     */
    protected $createdDate;


    const LOGGED_IN_USER = "LOGGED_IN_USER";


    /**
     * Create a new user with basic data.
     *
     * @param string $emailAddress
     * @param string $hashedPassword
     * @param string $name
     */
    public function __construct($emailAddress = null, $hashedPassword = null, $name = null, $parentAccountId = 0, $id = null) {
        $this->emailAddress = $emailAddress;
        if ($hashedPassword) {
            $this->setHashedPassword($hashedPassword);

        }
        $this->name = $name;
        $this->parentAccountId = $parentAccountId ? $parentAccountId : 0;
        $this->id = $id;

    }


    /**
     * @param string $emailAddress
     */
    public function setEmailAddress($emailAddress) {
        $this->emailAddress = $emailAddress;
    }


    /**
     * Get the full email address (in Name<email> format).
     */
    public function getFullEmailAddress() {
        return $this->name ? $this->name . " <" . $this->emailAddress . ">" : $this->emailAddress;
    }

    /**
     * @return string
     */
    public function getParentAccountId() {
        return $this->parentAccountId;
    }

    /**
     * @param string $parentAccountId
     */
    public function setParentAccountId($parentAccountId) {
        $this->parentAccountId = $parentAccountId;
    }

    /**
     * @return string
     */
    public function getHashedPassword() {
        return $this->hashedPassword;
    }

    /**
     * @param string $hashedPassword
     */
    public function setHashedPassword($hashedPassword) {
        $this->hashedPassword = $hashedPassword;
    }


    /**
     * Update with a new random password which will be returned plain from this function.
     * The hashed variant will be updated on this object.
     *
     * @return string
     */
    public function generateAndUpdatePassword() {

        /**
         * @var SHA512HashProvider $hashProvider
         */
        $hashProvider = Container::instance()->get(SHA512HashProvider::class);

        // Generate a new password
        $newPassword = StringUtils::generateRandomString(8, true, true, true);

        // Update the hashed password
        $this->setHashedPassword($hashProvider->generateHash($newPassword . $this->emailAddress));

        // Return the new password
        return $newPassword;
    }

    /**
     * Confirm whether the supplied password matches the hashed value
     *
     * @param $password
     * @return boolean
     */
    public function passwordMatches($password, $clientSalt) {

        /**
         * @var SHA512HashProvider $hashProvider
         */
        $hashProvider = Container::instance()->get(SHA512HashProvider::class);

        $expectedHash = $hashProvider->generateHash($this->hashedPassword . $clientSalt);
        return $password == $expectedHash;

    }


    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getMobileNumber() {
        return $this->mobileNumber;
    }

    /**
     * @param string $mobileNumber
     */
    public function setMobileNumber($mobileNumber) {
        $this->mobileNumber = $mobileNumber;
    }

    /**
     * @return string
     */
    public function getBackupEmailAddress() {
        return $this->backupEmailAddress;
    }

    /**
     * @param string $backupEmailAddress
     */
    public function setBackupEmailAddress($backupEmailAddress) {
        $this->backupEmailAddress = $backupEmailAddress;
    }


    public function getAccountIds() {
        $accountIds = array();
        foreach ($this->roles as $role) {
            if ($role->getAccountId() && $role->getAccountId() > 0)
                $accountIds[$role->getAccountId()] = 1;
        }
        return array_keys($accountIds);
    }


    /**
     * @return int
     */
    public function getActiveAccountId() {
        $firstActiveAccountId = null;

        $rolesByAccountId = ObjectArrayUtils::indexArrayOfObjectsByMember("accountId", $this->getRoles());

        foreach ($rolesByAccountId as $role) {

            if ($role->getAccountStatus() == Account::STATUS_ACTIVE) {

                if ($this->activeAccountId == $role->getAccountId())
                    return $role->getAccountId();

                if (!$firstActiveAccountId)
                    $firstActiveAccountId = $role->getAccountId();

            }
        }

        return $firstActiveAccountId;
    }

    /**
     * @param int $activeAccountId
     */
    public function setActiveAccountId($activeAccountId) {
        $this->activeAccountId = $activeAccountId;
    }

    /**
     * @param mixed $applicationSettings
     */
    public function setApplicationSettings($applicationSettings) {
        $this->applicationSettings = $applicationSettings;
    }


    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status) {
        if ($status == User::STATUS_ACTIVE && !$this->createdDate) {
            $this->createdDate = new \DateTime();
        }

        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getInvalidLoginAttempts() {
        return $this->invalidLoginAttempts;
    }

    /**
     * @param int $invalidLoginAttempts
     */
    public function setInvalidLoginAttempts($invalidLoginAttempts) {
        $this->invalidLoginAttempts = $invalidLoginAttempts;
    }

    /**
     * @param int $successfulLogins
     */
    public function setSuccessfulLogins($successfulLogins) {
        $this->successfulLogins = $successfulLogins;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedDate() {
        return $this->createdDate;
    }


    /**
     * Handle more advanced checking for no overlap of email addresses in same context
     *
     * @return array
     */
    public function validate() {

        $validationErrors = [];

        // Check for duplication of email address across parent accounts
        $matchingUsers = self::values("COUNT(*)", "WHERE emailAddress = ? AND parent_account_id = ? AND id <> ?", $this->emailAddress,
            $this->parentAccountId ? $this->parentAccountId : 0, $this->id ? $this->id : -1);


        if ($matchingUsers[0] > 0)
            $validationErrors["emailAddress"] = new FieldValidationError("emailAddress", "duplicateEmail", "A user already exists with this email address");


        // Check for previously used password if new one supplied
        if ($this->getId()) {
            $passwordChange = self::values("COUNT(*)", "WHERE id = ? AND hashedPassword <> ?",
                $this->getId(), $this->hashedPassword);

            if ($passwordChange[0] > 0) {
                $previousPassword = UserPasswordHistory::values("COUNT(*)", "WHERE user_id = ? AND hashed_password = ?", $this->getId(), $this->hashedPassword);
                if ($previousPassword[0] > 0)
                    $validationErrors["hashedPassword"] = new FieldValidationError("hashedPassword", "previousPassword", "The supplied password has been used before");
            }
        }

        return $validationErrors;
    }

    public function generateSummary() {
        return new UserSummary($this->name, $this->status, $this->emailAddress, $this->successfulLogins, $this->applicationSettings);
    }


}
