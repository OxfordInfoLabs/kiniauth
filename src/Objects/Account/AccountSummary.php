<?php


namespace Kiniauth\Objects\Account;
use Kinikit\Persistence\ORM\ActiveRecord;


/**
 * Account summary.  Used for listing accounts in both Admin and for a user.
 *
 * @table ka_account
 */
class AccountSummary extends ActiveRecord {

    /**
     * The account name - optional
     *
     * @var string
     */
    protected $name;


    /**
     * Auto increment id.  Strategically breaking naming convention to
     * enforce security based upon account id.
     *
     * @var integer
     * @primaryKey
     * @autoIncrement
     */
    protected $accountId;


    /**
     * @var integer
     */
    protected $parentAccountId = 0;


    /**
     * Status of the account in question.
     *
     * @var string
     * @maxLength 30
     */
    protected $status = self::STATUS_ACTIVE;


    const STATUS_ACTIVE = "ACTIVE";
    const STATUS_SUSPENDED = "SUSPENDED";


    /**
     * Create an account summary object
     *
     * @param integer $accountId
     * @param string $name
     * @param integer $parentAccountId
     */
    public function __construct($accountId = null, $name = null, $parentAccountId = 0) {
        $this->accountId = $accountId;
        $this->name = $name;
        $this->parentAccountId = $parentAccountId;
    }


    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getAccountId() {
        return $this->accountId;
    }

    /**
     * @return int
     */
    public function getParentAccountId() {
        return $this->parentAccountId;
    }


    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }


}
