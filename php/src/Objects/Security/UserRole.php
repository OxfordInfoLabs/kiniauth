<?php


namespace Kiniauth\Objects\Security;


use Kiniauth\Attributes\Security\AccessNonActiveScopes;

/**
 * Encodes a role for a user on an account.
 *
 * @table ka_user_role
 * @generate
 */
#[AccessNonActiveScopes]
class UserRole extends SecurableRole {

    /**
     * The user id for which this account role is being attached
     *
     * @var integer
     * @primaryKey
     */
    protected $userId;


    /**
     * Construct a new user account role object.
     *
     * @param string $scope
     * @param string $scopeId
     * @param integer $roleId
     * @param integer $userId
     */
    public function __construct($scope = Role::SCOPE_ACCOUNT, $scopeId = null, $roleId = null, $accountId = null, $userId = null) {
        parent::__construct($scope, $scopeId, $roleId, $accountId);
        $this->userId = $userId;
    }


    /**
     * @return int
     */
    public function getUserId() {
        return $this->userId;
    }


}
