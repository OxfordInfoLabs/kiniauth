<?php


namespace Kiniauth\ValueObjects\Security;


use Kiniauth\Objects\Security\Role;

class ScopeRoles {

    /**
     * @var string
     */
    private $scope;

    /**
     * @var string
     */
    private $scopeDescription;


    /**
     * @var Role[]
     */
    private $roles;

    /**
     * ScopeRoles constructor.
     * @param string $scope
     * @param string $scopeDescription
     * @param Role[] $roles
     */
    public function __construct($scope, $scopeDescription,  $roles) {
        $this->scope = $scope;
        $this->scopeDescription = $scopeDescription;
        $this->roles = $roles;
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
    public function getScopeDescription() {
        return $this->scopeDescription;
    }

    /**
     * @return Role
     */
    public function getRoles() {
        return $this->roles;
    }


}
