<?php


namespace Kiniauth\Objects\Account;

use Kiniauth\Attributes\Security\AccessNonActiveScopes;
use Kinikit\Persistence\ORM\ActiveRecord;


/**
 * Account summary.  Used for listing accounts in both Admin and for a user.
 *
 * @table ka_account
 */
#[AccessNonActiveScopes]
class AccountSummary extends ActiveRecord {

    /**
     * The account name - optional
     *
     * @var string
     * @maxLength 100
     */
    protected $name;


    /**
     * @var string
     * @maxLength 4000
     */
    protected $logo;


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
    protected $status = self::STATUS_PENDING;


    const STATUS_PENDING = "PENDING";
    const STATUS_ACTIVE = "ACTIVE";
    const STATUS_SUSPENDED = "SUSPENDED";


    /**
     * Create an account summary object
     *
     * @param integer $accountId
     * @param string $name
     * @param integer $parentAccountId
     * @param string $logo
     */
    public function __construct($accountId = null, $name = null, $parentAccountId = 0, $logo = null) {
        $this->accountId = $accountId;
        $this->name = $name;
        $this->parentAccountId = $parentAccountId;
        $this->logo = $logo;
    }


    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLogo() {
        return $this->logo;
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
