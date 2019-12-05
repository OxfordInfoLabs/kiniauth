<?php


namespace Kiniauth\Services\Security;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\ValueObjects\Security\AssignedRole;
use Kiniauth\ValueObjects\Security\ScopeRoles;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

/**
 * Service for managing the assignment and retrieval of roles.
 *
 * Class RoleService
 * @package Kiniauth\Services\Security
 */
class RoleService {


    /**
     * @var ScopeManager
     */
    private $scopeManager;


    /**
     * RoleService constructor.
     *
     * @param ScopeManager $scopeManager
     */
    public function __construct($scopeManager) {
        $this->scopeManager = $scopeManager;
    }


    /**
     * Get all possible account roles by scope - useful for drawing the roles GUI
     */
    public function getAllPossibleAccountScopeRoles($accountId = Account::LOGGED_IN_ACCOUNT) {

        $allRoles = ObjectArrayUtils::groupArrayOfObjectsByMember("scope", Role::filter("WHERE defined_account_id = $accountId OR defined_account_id IS NULL ORDER BY id"));

        $scopeRoles = [];
        foreach ($this->scopeManager->getScopeAccesses() as $scopeAccess) {

            $roles = $allRoles[$scopeAccess->getScope()] ?? [];

            $scopeRolesObject = new ScopeRoles($scopeAccess->getScope(), $scopeAccess->getScopeDescription(), $roles);
            $scopeRoles[] = $scopeRolesObject;
        }

        return $scopeRoles;
    }


    /**
     * Update assigned account roles for a user.  This effectively replaces all roles on the given account
     * for the passed user.
     *
     * @param integer $userId
     * @param integer $accountId
     * @param AssignedRole[] $assignedRoles
     *
     *
     */
    public function updateAssignedAccountRolesForUser($userId, $assignedRoles, $accountId = Account::LOGGED_IN_ACCOUNT, $newUserAccess = false) {

        // grab the roles matching the newly assigned roles.
        $roleIds = ObjectArrayUtils::getMemberValueArrayForObjects("roleId", $assignedRoles);

        try {
            $roles = Role::multiFetch($roleIds);

            // Create and save new user roles
            $candidateRoles = [];
            foreach ($assignedRoles as $assignedRole) {

                if (!$assignedRole->getScopeId())
                    throw new ObjectNotFoundException("", "");

                if (isset($roles[$assignedRole->getRoleId()])) {
                    $role = $roles[$assignedRole->getRoleId()];
                    $userRole = new UserRole($role->getScope(), $assignedRole->getScopeId(), $assignedRole->getRoleId(), $accountId, $userId);
                    $candidateRoles[] = $userRole;
                }
            }

            // Group roles by scope.
            $groupedCandidateRoles = ObjectArrayUtils::groupArrayOfObjectsByMember("scope", $candidateRoles);

            $newRoles = [];
            foreach ($this->scopeManager->getScopeAccesses() as $scopeAccess) {
                if (isset($groupedCandidateRoles[$scopeAccess->getScope()]))
                    $newRoles = array_merge($newRoles, $scopeAccess->getAssignableUserRoles($groupedCandidateRoles[$scopeAccess->getScope()]));
            }

            // Move old roles out of the way.
            $userRoles = UserRole::filter("WHERE userId = ? AND accountId = ?", $userId, $accountId);

            if (!$newUserAccess && (sizeof($userRoles) == 0))
                throw new AccessDeniedException("The passed user has no access to the account");

            foreach ($userRoles as $userRole) {
                $userRole->remove();
            }


            // Save new roles
            foreach ($newRoles as $newRole) {
                $newRole->save();
            }

        } catch (ObjectNotFoundException $e) {
            throw new ValidationException(["assignedRoles" => new FieldValidationError("assignedRoles", "invalid", "Invalid assigned roles passed to update roles for user")]);
        }


    }


}
