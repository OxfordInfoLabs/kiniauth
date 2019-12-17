<?php


namespace Kiniauth\Traits\Controller\Account;


use Kiniauth\Services\Security\ScopeManager;

trait Role {

    private $scopeManager;

    /**
     * Role constructor.
     * @param ScopeManager $scopeManager
     */
    public function __construct($scopeManager) {
        $this->scopeManager = $scopeManager;
    }

    /**
     * Get all scope accesses
     *
     * @http GET /scopeAccesses
     *
     * @return \Kiniauth\Services\Security\ScopeAccess[]
     */
    public function getScopeAccesses() {
        return $this->scopeManager->getScopeAccesses();
    }

}
