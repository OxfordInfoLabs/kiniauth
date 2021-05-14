<?php


namespace Kiniauth\Services\Security;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;

/**
 * Standard account object base scope access - implements the generateScopePrivileges
 * method with the usual standard logic for account objects as follows:
 *
 * - System and account superusers get full access to objects of this type.
 * - Other users require prescriptive assignment to access objects of this type.
 *
 * It is assumed that the account scope access will have already eliminated
 * access to non account objects using the account_id column.
 *
 * Class AccountObjectScopeAccess
 * @package Kiniauth\Services\Security
 */
abstract class StandardAccountObjectScopeAccess extends ScopeAccess {

    /**
     * Generate scope privileges for a user / account
     *
     * @param User $user
     * @param Account $account
     * @param string[] $accountPrivileges
     */
    public function generateScopePrivileges($user, $account, $accountPrivileges) {

        // If super user or account admin, shortcut this process and allow full access
        foreach ($accountPrivileges as $accountId => $privileges) {
            if (in_array("*", $privileges)) {
                return ["*" => ["*"]];
            }
        }

        $scopePrivileges = [];

        if ($user) {

            /**
             * @var $role UserRole
             */
            foreach ($user->getRoles() as $role) {

                if ($role->getScope() == $this->getScope()) {

                    if (!isset($scopePrivileges[$role->getScopeId()])) {
                        $scopePrivileges[$role->getScopeId()] = [];
                    }

                    $scopePrivileges[$role->getScopeId()] = array_merge($scopePrivileges[$role->getScopeId()], $role->getPrivileges());
                }
            }

        } else if ($account) {
            return ["*" => ["*"]];
        }


        return $scopePrivileges;
    }


}