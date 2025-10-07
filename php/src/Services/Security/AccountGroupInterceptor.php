<?php

namespace Kiniauth\Services\Security;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountGroup;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;

class AccountGroupInterceptor extends DefaultORMInterceptor {

    /**
     * @var SecurityService
     */
    private $securityService;

    /**
     * @param SecurityService $securityService
     */
    public function __construct(SecurityService $securityService) {
        $this->securityService = $securityService;
    }

    /**
     * @param AccountGroup $object
     * @return void
     */
    public function preSave($object) {

        if ($this->securityService->isSuperUserLoggedIn()) {
            return;
        } else {
            /** @var Account $loggedInAccount */
            $loggedInAccount = $this->securityService->getLoggedInSecurableAndAccount()[1];

            $members = array_map(fn ($groupMemberObj) => $groupMemberObj->getMemberAccountId(), $object->getAccountGroupMembers());

            if (!in_array($loggedInAccount?->getAccountId(), $members)) {
                throw new AccessDeniedException();
            }
        }

    }

    /**
     * @param AccountGroup $object
     * @return void
     */
    public function preDelete($object) {
        if ($this->securityService->isSuperUserLoggedIn()) {
            return;
        } else {
            /** @var Account $loggedInAccount */
            $loggedInAccount = $this->securityService->getLoggedInSecurableAndAccount()[1];

            if ($loggedInAccount?->getAccountId() != $object->getOwnerAccountId()) {
                throw new AccessDeniedException();
            }
        }
    }

}