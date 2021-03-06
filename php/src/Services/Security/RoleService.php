<?php


namespace Kiniauth\Services\Security;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\ValueObjects\Security\AssignedRole;
use Kiniauth\ValueObjects\Security\ScopeObjectRolesAssignment;
use Kiniauth\ValueObjects\Security\ScopeRoles;
use Kiniauth\ValueObjects\Security\ScopeObjectRoles;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Logging\Logger;
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
     *
     * @param integer $accountId
     *
     * @return ScopeRoles[]
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
     * Get filtered user assignable scope roles.  This returns an array of UserScopeRoles objects where the roles collection
     * represents just the roles which are assignable for the user.  If a filter string is passed this will be passed
     * as a filter to the scope object description and offset and limit will be applied to allow paging of these results.
     *
     * @param integer $userId
     * @param string $filterString
     * @param integer $offset
     * @param integer $limit
     * @param integer $accountId
     */
    public function getFilteredUserAssignableAccountScopeRoles($userId, $scope, $filterString = "", $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Get the scope access.
        $scopeAccess = $this->scopeManager->getScopeAccess($scope);

        $allScopeRoles = Role::filter("WHERE scope = ? ORDER BY id", $scope);

        // Grab matching descriptions
        $matchingDescriptions = $scopeAccess->getFilteredScopeObjectDescriptions($filterString, $offset, $limit, $accountId);

        // Loop through each matching description, create all possible user roles.
        $userRoles = [];
        foreach ($matchingDescriptions as $scopeId => $scopeObjectDescription) {

            foreach ($allScopeRoles as $scopeRole) {
                $userRoles[] = new UserRole($scope, $scopeId, $scopeRole->getId(), $accountId, $userId);
            }
        }

        // Eliminate unassignable roles
        $assignableUserRoles = ObjectArrayUtils::groupArrayOfObjectsByMember(["scopeId", "roleId"],
            $scopeAccess->getAssignableUserRoles($userRoles));


        // Now construct array of user scope roles
        $userScopeRoles = [];
        foreach ($matchingDescriptions as $scopeId => $scopeObjectDescription) {

            $roles = [];
            foreach ($allScopeRoles as $role) {
                if (isset($assignableUserRoles[$scopeId][$role->getId()])) {
                    $roles[$role->getId()] = $role;
                } else {
                    $roles[$role->getId()] = null;
                }
            }

            $userScopeRoles[] = new ScopeObjectRoles($scope, $scopeId, $scopeObjectDescription, $roles);
        }


        return $userScopeRoles;

    }


    /**
     * Get all user roles for the supplied account
     *
     * @param integer $userId
     * @param integer $accountId
     */
    public function getAllUserAccountRoles($userId, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $allUserRoles = UserRole::filter("WHERE userId = ? AND accountId = ?", $userId, $accountId);

        $groupedRoles = ObjectArrayUtils::groupArrayOfObjectsByMember(["scope", "scopeId"], $allUserRoles);

        $userScopeRolesArray = [];

        // All scopes
        $allScopes = $this->scopeManager->getScopeAccesses();

        // Loop through creating the correct structure
        foreach ($allScopes as $scope => $scopeAccess) {


            $scopeObjectRoles = $groupedRoles[$scope] ?? [];

            // Grab the scope description and create the array entry
            $scopeDescription = $scopeAccess->getScopeDescription();
            $userScopeRolesArray[$scopeDescription] = [];

            if (sizeof($scopeObjectRoles) > 0) {

                // Grab all object descriptions
                $scopeObjectDescriptions = $scopeAccess->getScopeObjectDescriptionsById(array_keys($scopeObjectRoles), $accountId);


                foreach ($scopeObjectRoles as $scopeId => $userRoles) {
                    $roles = ObjectArrayUtils::getMemberValueArrayForObjects("role", $userRoles);
                    $userScopeRolesArray[$scopeDescription][] = new ScopeObjectRoles($scope, $scopeId, $scopeObjectDescriptions[$scopeId], $roles);
                }
            }


        }


        return $userScopeRolesArray;


    }


    /**
     * Update the roles for a given scope object for a user using a Scope Object Roles object.  This operates only on the
     * passed account (defaulting to the logged in account).
     *
     * @param integer $userId
     * @param ScopeObjectRolesAssignment[] $scopeObjectRolesAssignments
     */
    public function updateAssignedScopeObjectRolesForUser($userId, $scopeObjectRolesAssignments, $accountId = Account::LOGGED_IN_ACCOUNT) {


        /**
         * Process each scope object roles assignment object
         */
        foreach ($scopeObjectRolesAssignments as $scopeObjectRolesAssignment) {


            // Grab the roles
            $roleIds = $scopeObjectRolesAssignment->getRoleIds();

            $candidateRoles = [];

            $realRoleIds = [];
            foreach ($roleIds as $roleId) {
                if (is_numeric($roleId))
                    $realRoleIds[] = $roleId;
                else if (is_null($roleId))
                    $candidateRoles[] = new UserRole($scopeObjectRolesAssignment->getScope(), $scopeObjectRolesAssignment->getScopeId(), null, $accountId, $userId);
            }

            $roles = sizeof($realRoleIds) > 0 ? Role::multiFetch($realRoleIds) : [];
            // Create and save new user roles
            foreach ($realRoleIds as $roleId) {
                if (isset($roles[$roleId])) {
                    $role = $roles[$roleId];
                    $userRole = new UserRole($role->getScope(), $scopeObjectRolesAssignment->getScopeId(), $roleId, $accountId, $userId);
                    $candidateRoles[] = $userRole;
                }
            }

            // Limit the roles to just assignable ones.
            $scopeAccess = $this->scopeManager->getScopeAccess($scopeObjectRolesAssignment->getScope());
            $newRoles = $scopeAccess->getAssignableUserRoles($candidateRoles);

            // Move old roles out of the way.
            $userRoles = UserRole::filter("WHERE userId = ? AND accountId = ? AND scope = ? AND scope_id = ?", $userId, $accountId, $scopeObjectRolesAssignment->getScope(), $scopeObjectRolesAssignment->getScopeId());
            foreach ($userRoles as $userRole) {
                $userRole->remove();
            }

            // Save new roles
            foreach ($newRoles as $newRole) {
                $newRole->save();
            }


        }


    }


}
