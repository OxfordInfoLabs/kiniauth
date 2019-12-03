<?php


namespace Kiniauth\ValueObjects\Security;

/**
 * Value object used to encode roles to be assigned to a user.
 *
 * Class AssignedRole
 * @package Kiniauth\ValueObjects\Security
 */
class AssignedRole {

    /**
     * The id of the role to assign.
     *
     * @var integer
     */
    private $roleId;


    /**
     * The scope id for this role or left blank if this is an account role.
     *
     * @var integer
     */
    private $scopeId;

    /**
     * AssignedRole constructor.
     *
     * @param int $roleId
     * @param int $scopeId
     */
    public function __construct($roleId, $scopeId) {
        $this->roleId = $roleId;
        $this->scopeId = $scopeId;
    }


    /**
     * @return int
     */
    public function getRoleId() {
        return $this->roleId;
    }

    /**
     * @param int $roleId
     */
    public function setRoleId($roleId) {
        $this->roleId = $roleId;
    }

    /**
     * @return int
     */
    public function getScopeId() {
        return $this->scopeId;
    }

    /**
     * @param int $scopeId
     */
    public function setScopeId($scopeId) {
        $this->scopeId = $scopeId;
    }


}
