<?php


namespace Kiniauth\ValueObjects\Security;


use Kiniauth\Objects\Security\Role;

class ScopeObjectRoles {


    /**
     * @var string
     */
    private $scope;

    /**
     * @var integer
     */
    private $scopeId;

    /**
     * @var string
     */
    private $scopeObjectDescription;


    /**
     * @var Role[]
     */
    private $roles;

    /**
     * UserScopeRoles constructor.
     *
     * @param string $scope
     * @param int $scopeId
     * @param string $scopeObjectDescription
     * @param Role[] $roles
     */
    public function __construct($scope, $scopeId, $scopeObjectDescription, $roles) {
        $this->scope = $scope;
        $this->scopeId = $scopeId;
        $this->scopeObjectDescription = $scopeObjectDescription;
        $this->roles = $roles;
    }

    /**
     * @return string
     */
    public function getScope() {
        return $this->scope;
    }

    /**
     * @return int
     */
    public function getScopeId() {
        return $this->scopeId;
    }

    /**
     * @return string
     */
    public function getScopeObjectDescription() {
        return $this->scopeObjectDescription;
    }

    /**
     * @return Role[]
     */
    public function getRoles() {
        return $this->roles;
    }


}
