<?php


namespace Kiniauth\Services\Security;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\APIKeyRole;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\ValueObjects\Security\AssignedRole;
use Kiniauth\ValueObjects\Security\ScopeObjectRoles;
use Kiniauth\ValueObjects\Security\ScopeObjectRolesAssignment;
use Kiniauth\ValueObjects\Security\ScopeRoles;
use Kinikit\Core\Util\ObjectArrayUtils;

/**
 * Service for managing the assignment and retrieval of roles.
 *
 * Class RoleService
 * @package Kiniauth\Services\Security
 */
class RoleService {

    public function __construct(
        private ScopeManager $scopeManager
    ) {
    }


    /**
     * Get all possible account roles by scope - useful for drawing the roles GUI
     *
     * @param string $appliesTo
     * @param integer $accountId
     *
     * @return ScopeRoles[]
     */
    public function getAllPossibleAccountScopeRoles($appliesTo = Role::APPLIES_TO_USER, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $allRoles = ObjectArrayUtils::groupArrayOfObjectsByMember("scope", Role::filter("WHERE (defined_account_id = ?
            OR defined_account_id IS NULL)
            AND (appliesTo = ? OR appliesTo = 'ALL') 
            ORDER BY id", $accountId, $appliesTo));

        $scopeRoles = [];
        foreach ($this->scopeManager->getScopeAccesses() as $scopeAccess) {

            $roles = $allRoles[$scopeAccess->getScope()] ?? [];

            $scopeRolesObject = new ScopeRoles($scopeAccess->getScope(), $scopeAccess->getScopeDescription(), $roles);
            $scopeRoles[] = $scopeRolesObject;
        }

        return $scopeRoles;
    }


    /**
     * Get filtered assignable account scope roles for a given scope and applicable type (user / apikey)
     *
     * @param string $appliesTo
     * @param integer $securableId
     * @param string $scope
     * @param string $filterString
     * @param int $offset
     * @param int $limit
     * @param string $accountId
     *
     * @return ScopeObjectRoles[]
     */
    public function getFilteredAssignableAccountScopeRoles($appliesTo, $securableId, $scope, $filterString = "", $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Get the scope access.
        $scopeAccess = $this->scopeManager->getScopeAccess($scope);

        $allScopeRoles = Role::filter("WHERE scope = ? and (appliesTo = ? OR appliesTo = 'ALL') ORDER BY id", $scope, $appliesTo);

        // Grab matching descriptions
        $matchingDescriptions = $scopeAccess->getFilteredScopeObjectDescriptions($filterString, $offset, $limit, $accountId);

        // Loop through each matching description, create all possible user roles.
        $securableRoles = [];
        foreach ($matchingDescriptions as $scopeId => $scopeObjectDescription) {

            foreach ($allScopeRoles as $scopeRole) {
                if ($appliesTo == Role::APPLIES_TO_USER)
                    $securableRoles[] = new UserRole($scope, $scopeId, $scopeRole->getId(), $accountId, $securableId);
                else if ($appliesTo == Role::APPLIES_TO_API_KEY)
                    $securableRoles[] = new APIKeyRole($scope, $scopeId, $scopeRole->getId(), $accountId, $securableId);
            }
        }

        // Eliminate unassignable roles
        $assignableUserRoles = ObjectArrayUtils::groupArrayOfObjectsByMember(["scopeId", "roleId"],
            $scopeAccess->getAssignableSecurableRoles($securableRoles));


        // Now construct array of user scope roles
        $securableScopeRoles = [];
        foreach ($matchingDescriptions as $scopeId => $scopeObjectDescription) {

            $roles = [];
            foreach ($allScopeRoles as $role) {
                if (isset($assignableUserRoles[$scopeId][$role->getId()])) {
                    $roles[$role->getId()] = $role;
                } else {
                    $roles[$role->getId()] = null;
                }
            }

            $securableScopeRoles[] = new ScopeObjectRoles($scope, $scopeId, $scopeObjectDescription, $roles);
        }


        return $securableScopeRoles;

    }


    /**
     * Get all user roles for the supplied account
     *
     * @param string $appliesTo
     * @param integer $securableId
     * @param integer $accountId
     */
    public function getAllAccountRoles($appliesTo, $securableId, $accountId = Account::LOGGED_IN_ACCOUNT) {

        if ($appliesTo == Role::APPLIES_TO_USER) {
            $allSecurableRoles = UserRole::filter("WHERE userId = ? AND accountId = ?", $securableId, $accountId);
        } else {
            $allSecurableRoles = APIKeyRole::filter("WHERE apiKeyId = ? AND accountId = ?", $securableId, $accountId);
        }

        $groupedRoles = ObjectArrayUtils::groupArrayOfObjectsByMember(["scope", "scopeId"], $allSecurableRoles);

        $scopeRolesArray = [];

        // All scopes
        $allScopes = $this->scopeManager->getScopeAccesses();

        // Loop through creating the correct structure
        foreach ($allScopes as $scope => $scopeAccess) {


            $scopeObjectRoles = $groupedRoles[$scope] ?? [];

            // Grab the scope description and create the array entry
            $scopeDescription = $scopeAccess->getScopeDescription();
            $scopeRolesArray[$scopeDescription] = [];

            if (sizeof($scopeObjectRoles) > 0) {

                // Grab all object descriptions
                $scopeObjectDescriptions = $scopeAccess->getScopeObjectDescriptionsById(array_keys($scopeObjectRoles), $accountId);


                foreach ($scopeObjectRoles as $scopeId => $userRoles) {
                    $roles = ObjectArrayUtils::getMemberValueArrayForObjects("role", $userRoles);
                    $scopeRolesArray[$scopeDescription][] = new ScopeObjectRoles($scope, $scopeId, $scopeObjectDescriptions[$scopeId], $roles);
                }
            }


        }


        return $scopeRolesArray;


    }


    /**
     * Update the roles for a given scope object for a user using a Scope Object Roles object.  This operates only on the
     * passed account (defaulting to the logged in account).
     *
     * @param $appliesTo
     * @param integer $securableId
     * @param ScopeObjectRolesAssignment[] $scopeObjectRolesAssignments
     */
    public function updateAssignedScopeObjectRoles($appliesTo, $securableId, $scopeObjectRolesAssignments, $accountId = Account::LOGGED_IN_ACCOUNT) {
        /**
         * Process each scope object roles assignment object
         */
        foreach ($scopeObjectRolesAssignments as $scopeObjectRolesAssignment) {


            // Grab the roles
            $roleIds = $scopeObjectRolesAssignment->getRoleIds();

            $candidateRoles = [];

            $realRoleIds = [];
            foreach ($roleIds as $roleId) {
                if (is_numeric($roleId) && $roleId > 0)
                    $realRoleIds[] = $roleId;
                else {
                    if ($appliesTo == Role::APPLIES_TO_USER)
                        $candidateRoles[] = new UserRole($scopeObjectRolesAssignment->getScope(), $scopeObjectRolesAssignment->getScopeId(), 0, $accountId, $securableId);
                    else
                        $candidateRoles[] = new APIKeyRole($scopeObjectRolesAssignment->getScope(), $scopeObjectRolesAssignment->getScopeId(), 0, $accountId, $securableId);
                }
            }

            $roles = sizeof($realRoleIds) > 0 ? Role::multiFetch($realRoleIds) : [];
            // Create and save new user roles
            foreach ($realRoleIds as $roleId) {
                if (isset($roles[$roleId])) {
                    $role = $roles[$roleId];
                    if ($appliesTo == Role::APPLIES_TO_USER)
                        $securableRole = new UserRole($role->getScope(), $scopeObjectRolesAssignment->getScopeId(), $roleId, $accountId, $securableId);
                    else
                        $securableRole = new APIKeyRole($role->getScope(), $scopeObjectRolesAssignment->getScopeId(), $roleId, $accountId, $securableId);
                    $candidateRoles[] = $securableRole;
                }
            }

            // Limit the roles to just assignable ones.
            $scopeAccess = $this->scopeManager->getScopeAccess($scopeObjectRolesAssignment->getScope());
            $newRoles = $scopeAccess->getAssignableSecurableRoles($candidateRoles);

            // Move old roles out of the way.
            if ($appliesTo == Role::APPLIES_TO_USER) {
                $userRoles = UserRole::filter("WHERE userId = ? AND accountId = ? AND scope = ? AND scope_id = ?", $securableId, $accountId, $scopeObjectRolesAssignment->getScope(), $scopeObjectRolesAssignment->getScopeId());
                foreach ($userRoles as $userRole) {
                    $userRole->remove();
                }
            } else {
                $apiKeyRoles = APIKeyRole::filter("WHERE apiKeyId = ? AND accountId = ? AND scope = ? AND scope_id = ?", $securableId, $accountId, $scopeObjectRolesAssignment->getScope(), $scopeObjectRolesAssignment->getScopeId());
                foreach ($apiKeyRoles as $apiKeyRole) {
                    $apiKeyRole->remove();
                }
            }

            // Save new roles
            foreach ($newRoles as $newRole) {
                $newRole->save();
            }


        }


    }


}
