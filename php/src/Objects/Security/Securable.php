<?php


namespace Kiniauth\Objects\Security;


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
     * Get the active account id for this securable
     *
     * @return integer
     */
    public abstract function getActiveAccountId();


    /**
     * Return status for this item
     *
     * @return string
     */
    public abstract function getStatus();

}