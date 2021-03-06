<?php


namespace Kiniauth\Services\Security;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Security\Privilege;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Util\ObjectArrayUtils;

/**
 * Account scope access class - generates set of account privileges using a set of user roles.
 *
 * Class AccountScopeAccess
 * @package Kiniauth\Services\Security
 */
class AccountScopeAccess extends ScopeAccess {

    /**
     * AccountScopeAccess constructor.
     *
     */
    public function __construct() {
        parent::__construct(Role::SCOPE_ACCOUNT, "Account", "accountId");
    }


    /**
     * Generate scope privileges from either a user or an account (only one will be passed).
     * if an account is passed, it means it is an account based log in so will generally be granted full access to account items.
     *
     * This is used on log in to determine access to items of this scope type.  It should return an array of privilege keys indexed by the id of the
     * scope item.  The indexed array of account privileges is passed through for convenience.
     *
     * Use * as the scope key to indicate all accounts.
     *
     *
     * @param User $user
     * @param Account $account
     * @param string[] $accountPrivileges
     *
     * @return array
     */
    public function generateScopePrivileges($user, $account, $accountPrivileges) {

        $scopePrivileges = array();
        $superUser = false;
        $accountIds = array();


        if ($user) {

            /**
             * @var $role UserRole
             */
            foreach ($user->getRoles() as $role) {

                // Only assess roles of type Account or Parent Account.
                if ($role->getScope() == Role::SCOPE_ACCOUNT || $role->getScope() == Role::SCOPE_PARENT_ACCOUNT) {

                    if (!$role->getScopeId()) {
                        $accountId = "*";
                        $superUser = true;
                    } else {
                        $accountId = $role->getScopeId();
                        $accountIds[] = $accountId;
                    }

                    if ($role->getRoleId()) {
                        $privileges = $role->getPrivileges();
                    } else {
                        $privileges = ["*"];
                    }


                    if (!isset($scopePrivileges[$accountId])) {
                        $scopePrivileges[$accountId] = array();
                    }

                    $scopePrivileges[$accountId] = $scopePrivileges[$accountId] + $privileges;

                }


            }
        } else if ($account) {
            $scopePrivileges[$account->getAccountId()] = ["*"];
            $accountIds = [$account->getAccountId()];
        }

        // If we have at least one account, check for child accounts and add privileges for these.
        if (!$superUser && sizeof($accountIds) > 0) {


            $childAccounts = AccountSummary::filter("WHERE parent_account_id IN (" . join(",", $accountIds) . ")");

            foreach ($childAccounts as $childAccount) {

                if (!isset($scopePrivileges[$childAccount->getAccountId()])) {
                    $targetPrivilege = (in_array("*", $scopePrivileges[$childAccount->getParentAccountId()])) ? "*" : Privilege::PRIVILEGE_ACCESS;
                    $scopePrivileges[$childAccount->getAccountId()] = [$targetPrivilege];
                }
            }


        }


        return $scopePrivileges;

    }


    /**
     * Return labels matching each scope id.  This enables the generic role assignment screen
     * to show sensible values.
     *
     * @param $scopeIds
     * @param null $accountId
     * @return mixed
     */
    public function getScopeObjectDescriptionsById($scopeIds, $accountId = null) {

        $accounts = AccountSummary::multiFetch($scopeIds);
        return ObjectArrayUtils::getMemberValueArrayForObjects("name", $accounts);

    }

    /**
     * Get filtered scope object descriptions with offset and limiting for paging purposes.  If supplied, the
     * account id will be used to filter these if required.
     *
     * @param string $searchFilter
     * @param integer $accountId
     */
    public function getFilteredScopeObjectDescriptions($searchFilter, $offset = 0, $limit = 10, $accountId = null) {

        $account = AccountSummary::fetch($accountId);
        return [$account->getAccountId() => $account->getName()];

    }


}
