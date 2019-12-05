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
     * @return mixed
     */
    public function getScopeObjectLabelsById($scopeIds) {
        $labels = [];
        foreach ($scopeIds as $scopeId) {
            $labels[$scopeId] = "EXAMPLE $scopeId";
        }
        return $labels;
    }
}
