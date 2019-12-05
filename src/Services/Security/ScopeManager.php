<?php


namespace Kiniauth\Services\Security;


use Kinikit\Core\Util\ObjectArrayUtils;

class ScopeManager {

    /**
     * @var ScopeAccess[]
     */
    private $scopeAccesses = [];


    /**
     * ScopeManager constructor.
     *
     * @param AccountScopeAccess $accountScopeAccess
     */
    public function __construct($accountScopeAccess) {
        $this->scopeAccesses[$accountScopeAccess->getScope()] = $accountScopeAccess;
    }


    /**
     * Add a scope access to the array of scope accesses.
     *
     * @param ScopeAccess $scopeAccess
     */
    public function addScopeAccess($scopeAccess) {
        $this->scopeAccesses[$scopeAccess->getScope()] = $scopeAccess;
    }

    /**
     * Get the scope access for a given scope
     *
     * @return ScopeAccess
     */
    public function getScopeAccess($scope) {
        return $this->scopeAccesses[$scope];
    }

    /**
     * @return ScopeAccess[]
     */
    public function getScopeAccesses() {
        return $this->scopeAccesses;
    }


}
