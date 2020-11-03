<?php


namespace Kiniauth\Objects\Security;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Application\Session;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\ValidationException;
use Kinikit\Core\Security\Hash\HashProvider;
use Kinikit\Core\Security\Hash\SHA512HashProvider;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Core\Validation\FieldValidationError;


/**
 * Main user entity for accessing the system.  Users typically belong to one or more accounts or are super users.
 *
 * @table ka_user
 * @generate
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
     * Optional two factor authentication data if this has been enabled.
     *
     * @var string
     * @maxLength 2000
     */
    protected $twoFactorData;

    /**
     * Set of backup emergency codes used for 2fa where the original method can't be used.
     *
     * @var string[]
     * @json
     * @sqlType LONGTEXT
     */
    protected $backupCodes;


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

    // Salt prefix for password - BLOWFISH bcrypt
    const PASSWORD_SALT_PREFIX = "$2a$10$";


    /**
     * Create a new user with basic data.
     *
     * @param string $emailAddress
     * @param string $hashedPassword
     * @param string $name
     */
    public function __construct($emailAddress = null, $hashedPassword = null, $name = null, $parentAccountId = 0) {
        $this->emailAddress = $emailAddress;
        if ($hashedPassword) {
            $this->setHashedPassword($hashedPassword);

        }
        $this->name = $name;
        $this->parentAccountId = $parentAccountId ? $parentAccountId : 0;

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
    public function getTwoFactorData() {
        return $this->twoFactorData;
    }

    /**
     * @param string $twoFactorData
     */
    public function setTwoFactorData($twoFactorData) {
        $this->twoFactorData = $twoFactorData;
    }

    /**
     * @return string[]
     */
    public function getBackupCodes() {
        return $this->backupCodes;
    }

    /**
     * @param string[] $backupCodes
     */
    public function setBackupCodes($backupCodes) {
        $this->backupCodes = $backupCodes;
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


    public function getAccountIds() {
        $accountIds = array();
        foreach ($this->roles as $role) {
            if ($role->getScopeId())
                $accountIds[$role->getScopeId()] = 1;
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

        // Check for duplication across parent accounts
        $matchingUsers = self::values("COUNT(*)", "WHERE emailAddress = ? AND parent_account_id = ? AND id <> ?", $this->emailAddress,
            $this->parentAccountId ? $this->parentAccountId : 0, $this->id ? $this->id : -1);


        if ($matchingUsers[0] > 0)
            $validationErrors["emailAddress"] = new FieldValidationError("emailAddress", "duplicateEmail", "A user already exists with this email address");

        return $validationErrors;
    }

    public function generateSummary() {
        return new UserSummary($this->name, $this->status, $this->emailAddress, $this->successfulLogins);
    }


}
