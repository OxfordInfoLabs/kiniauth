<?php


namespace Kiniauth\Traits\Controller\API;


use Kiniauth\Objects\Account\AccountSummary;

trait Account {

    private $securityService;

    /**
     * Construct with a security service.
     *
     * Account constructor.
     *
     * @param \Kiniauth\Services\Security\SecurityService $securityService
     */
    public function __construct($securityService) {
        $this->securityService = $securityService;
    }


    /**
     * @http GET /
     *
     * @return \Kiniauth\Objects\Account\AccountSummary
     */
    public function getAccount() {
        list($user, $account) = $this->securityService->getLoggedInSecurableAndAccount();
        return $account->generateSummary();
    }

}
