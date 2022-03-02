<?php


namespace Kiniauth\ValueObjects\Security;

/**
 * Used to assign scope object roles
 *
 * Class ScopeObjectRolesAssignment
 * @package Kiniauth\ValueObjects\Security
 */
class ScopeObjectRolesAssignment {

    /**
     * The scope for this roles assignment
     *
     * @var string
     */
    private $scope;


    /**
     * The id of the scope object
     *
     * @var string
     */
    private $scopeId;


    /**
     * The ids for all roles to assign.
     *
     * @var integer[]
     */
    private $roleIds;

    /**
     * ScopeObjectRolesAssignment constructor.
     * @param string $scope
     * @param string $scopeId
     * @param integer[] $roleIds
     */
    public function __construct($scope, $scopeId, $roleIds) {
        $this->scope = $scope;
        $this->scopeId = $scopeId;
        $this->roleIds = $roleIds;
    }


    /**
     * @return string
     */
    public function getScope() {
        return $this->scope;
    }

    /**
     * @return string
     */
    public function getScopeId() {
        return $this->scopeId;
    }

    /**
     * @return integer[]
     */
    public function getRoleIds() {
        return $this->roleIds;
    }


}
