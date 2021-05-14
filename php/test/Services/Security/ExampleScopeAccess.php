<?php


namespace Kiniauth\Test\Services\Security;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Security\ScopeAccess;

class ExampleScopeAccess extends ScopeAccess {


    public function __construct() {
        parent::__construct("EXAMPLE", "Example");
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
     * @param User $user
     * @param Account $account
     * @param string[] $accountPrivileges
     *
     * @return
     */
    public function generateScopePrivileges($user, $account, $accountPrivileges) {

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
        $labels = [];
        foreach ($scopeIds as $scopeId) {
            $labels[$scopeId] = "EXAMPLE $scopeId";
        }
        return $labels;
    }


    /**
     * Get filtered scope object descriptions with offset and limiting for paging purposes.  If supplied, the
     * account id will be used to filter these if required.
     *
     * @param string $searchFilter
     * @param integer $accountId
     */
    public function getFilteredScopeObjectDescriptions($searchFilter, $offset = 0, $limit = 10, $accountId = null) {

        $descriptions = [];
        for ($i = 0; $i < 5; $i++) {
            $description = "EXAMPLE " . ($i + 1);
            if (!$searchFilter || strpos($description, $searchFilter) !== null)
                $descriptions[$i + 1] = $description;
        }

        return $descriptions;

    }

    public function getAssignableUserRoles($userRoles) {

        $assignables = [];
        foreach ($userRoles as $userRole) {
            if ($userRole->getRoleId() != 5)
                $assignables[] = $userRole;
        }

        return $assignables;
    }


}
