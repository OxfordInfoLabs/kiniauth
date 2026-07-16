<?php


namespace Kiniauth\Objects\Security;


use Kiniauth\Objects\Account\Account;
use Kinikit\Persistence\ORM\ActiveRecord;

abstract class Securable extends ActiveRecord {

    /**
     * An array of explicit role objects
     *
     * @unmapped
     */
    protected $roles = array();


    /**
     * @return mixed[]
     */
    public function getRoles() {
        return $this->roles;
    }

    /**
     * @param mixed[] $roles
     */
    public function setRoles($roles) {
        $this->roles = $roles;
    }

    /**
     * Get account ids for this securable
     *
     * @return array
     */
    public function getAccountIds() {
        $accountIds = array();
        foreach ($this->roles as $role) {
            if ($role->getAccountId() && $role->getAccountId() > 0)
                $accountIds[$role->getAccountId()] = 1;
        }
        return array_keys($accountIds);
    }

    /**
     * Get the active account id for this securable
     *
     * @return integer
     */
    public abstract function getActiveAccountId();

    /**
     * Get the most relevant account status for inactive accounts
     *
     * @return string
     */
    public function getInactiveAccountStatus() {
        $suspended = false;
        foreach ($this->roles as $role) {
            print_r($role);
            if ($role->getAccountStatus() == Account::STATUS_EXPIRED) {
                return Account::STATUS_EXPIRED;
            }
            if ($role->getAccountStatus() == Account::STATUS_SUSPENDED) {
                $suspended = true;
            }
        }
        if ($suspended) {
            return Account::STATUS_SUSPENDED;
        } else {
            return null;
        }
    }


    /**
     * Return status for this item
     *
     * @return string
     */
    public abstract function getStatus();

}