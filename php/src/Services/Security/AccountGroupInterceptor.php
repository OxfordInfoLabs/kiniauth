<?php

namespace Kiniauth\Services\Security;

use Kiniauth\Exception\Security\InvalidAccountGroupOwnerException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountGroup;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;
use Throwable;

class AccountGroupInterceptor extends DefaultORMInterceptor {

    /**
     * @var SecurityService
     */
    private SecurityService $securityService;

    private bool $disabled = false;

    /**
     * @param SecurityService $securityService
     */
    public function __construct(SecurityService $securityService) {
        $this->securityService = $securityService;
    }

    public function postMap($object): bool {

        if ($this->securityService->isSuperUserLoggedIn() || $this->disabled) {
            return true;
        }

        $loggedInAccount = $this->getLoggedInAccount();

        if (!$loggedInAccount) {
            return false;
        }

        $members = array_map(
            fn($groupMemberObj) => $groupMemberObj->getMemberAccountId(),
            $object->getAccountGroupMembers()
        );

        return in_array($loggedInAccount->getAccountId(), $members);
    }

    /**
     * @param AccountGroup $object
     *
     * @return void
     * @throws AccessDeniedException
     */
    public function preSave($object): void {

        if ($this->securityService->isSuperUserLoggedIn() || $this->disabled) {
            return;
        } else {
            $loggedInAccount = $this->getLoggedInAccount();

            $members = array_map(fn($groupMemberObj) => $groupMemberObj->getMemberAccountId(), $object->getAccountGroupMembers());

            if (!in_array($loggedInAccount?->getAccountId(), $members)) {
                throw new AccessDeniedException();
            }
        }

    }

    /**
     * @param AccountGroup $object
     *
     * @return void
     * @throws InvalidAccountGroupOwnerException
     */
    public function preDelete($object): void {
        if ($this->securityService->isSuperUserLoggedIn() || $this->disabled) {
            return;
        } else {
            $loggedInAccount = $this->getLoggedInAccount();

            if ($loggedInAccount?->getAccountId() != $object->getOwnerAccountId()) {
                throw new InvalidAccountGroupOwnerException();
            }
        }
    }

    /**
     * Execute a callable block insecurely with interceptors disabled.
     *
     * @param callable $callable
     *
     * @return mixed
     * @throws Throwable
     */
    public function executeInsecure(callable $callable): mixed {

        $previousDisabled = $this->disabled;

        // Disable for the duration of the callable
        $this->disabled = true;

        // Run the callable
        try {
            return $callable();
        } finally {
            $this->disabled = $previousDisabled;
        }
    }

    private function getLoggedInAccount(): ?Account
    {
        $loggedInContext = $this->securityService->getLoggedInSecurableAndAccount();

        /** @var Account $loggedInAccount */
        return $loggedInContext[1] ?? null;
    }

}