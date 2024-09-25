<?php

namespace Kiniauth\Services\Security;


use Kiniauth\Exception\Security\NoObjectGrantAccessException;
use Kiniauth\Exception\Security\ObjectNotSharableException;
use Kiniauth\Objects\Communication\Email\AccountTemplatedEmail;
use Kiniauth\Objects\Security\ObjectScopeAccess;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\Traits\Security\Sharable;
use Kiniauth\ValueObjects\Security\ScopeAccessGroup;
use Kiniauth\ValueObjects\Security\SharableItem;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Reflection\ClassInspector;
use Kinikit\Core\Util\ArrayUtils;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Persistence\ORM\ORM;

/**
 * Service for managing particularly creation and management of object scope accesses.
 */
class ObjectScopeAccessService {

    /**
     * Construct with security service for resolving permissions
     *
     * @param SecurityService $securityService
     */
    public function __construct(
        private SecurityService      $securityService,
        private ORM                  $orm,
        private ScopeManager         $scopeManager,
        private PendingActionService $pendingActionService,
        private EmailService         $emailService,
        private ObjectBinder         $objectBinder
    ) {
    }


    /**
     * Get all scope access groups for an object.  Disable object interceptor to allow object access
     *
     * @param string $objectClassName
     * @param string $objectPrimaryKey
     *
     * @return ScopeAccessGroup[]
     *
     * @objectInterceptorDisabled
     */
    public function getScopeAccessGroupsForObject(string $objectClassName, string $objectPrimaryKey) {

        // Check sharable and return
        $object = $this->checkObjectSharableByLoggedInUser($objectClassName, $objectPrimaryKey);

        // Get all scope groups.
        $scopeGroups = $object->returnValidScopeAccessGroups();

        // Add descriptions
        $this->addScopeObjectDescriptionsToScopeGroups($scopeGroups);

        return $scopeGroups;


    }


    /**
     * Assign one or more scope access groups to an object.  This will effectively replace any existing entries for the
     * passed groups.
     *
     * @param string $objectClassName
     * @param string $objectPrimaryKey
     * @param ScopeAccessGroup[] $scopeAccessGroups
     * @return void
     */
    public function assignScopeAccessGroupsToObject(string $objectClassName, string $objectPrimaryKey, array $scopeAccessGroups) {

        // Check that the class being assigned is sharable
        $this->checkObjectSharableByLoggedInUser($objectClassName, $objectPrimaryKey);

        // Save all object scope accesses
        foreach ($scopeAccessGroups as $group) {
            foreach ($group->getScopeAccesses() as $objectScopeItem) {
                $objectScopeAccess = new ObjectScopeAccess($objectScopeItem->getScope(), $objectScopeItem->getItemIdentifier(), $group->getGroupName(), $group->getWriteAccess(), $group->getGrantAccess(), $group->getExpiryDate(), $objectClassName, $objectPrimaryKey);
                $objectScopeAccess->save();
            }
        }


    }

    /**
     * Remove scope access groups from an object
     *
     * @param string $objectClassName
     * @param string $objectPrimaryKey
     * @param array $accessGroups
     * @return void
     */
    public function removeScopeAccessGroupsFromObject(string $objectClassName, string $objectPrimaryKey, array $accessGroups) {

        // Check the class being assigned is sharable
        $this->checkObjectSharableByLoggedInUser($objectClassName, $objectPrimaryKey);

        // Remove all entries for the specified access groups
        foreach ($accessGroups as $accessGroup) {
            $matchingScopes = ObjectScopeAccess::filter("WHERE sharedObjectClassName = ? AND sharedObjectPrimaryKey = ? AND accessGroup = ?",
                $objectClassName, $objectPrimaryKey,
                $accessGroup);
            foreach ($matchingScopes as $scope) {
                $scope->remove();
            }
        }


    }


    /**
     * Create invites for accounts contained within the supplied access groups
     *
     * @param string $objectClassName
     * @param string $objectPrimaryKey
     * @param ScopeAccessGroup[] $accessGroups
     * @param string $emailTemplate
     *
     * @return void
     *
     * @objectInterceptorDisabled
     */
    public function inviteAccountAccessGroupsToShareObject(string $objectClassName, string $objectPrimaryKey,
                                                           array  $accessGroups, string $emailTemplate) {


        // Check the class being assigned is sharable
        $sharable = $this->checkObjectSharableByLoggedInUser($objectClassName, $objectPrimaryKey);

        // Grab logged in use and account
        list($loggedInUser, $loggedInAccount) = $this->securityService->getLoggedInSecurableAndAccount();


        // Existing access groups
        $existingAccessGroups = ObjectArrayUtils::indexArrayOfObjectsByMember("groupName", $this->listInvitationsForSharedObject($objectClassName, $objectPrimaryKey));

        // Create a pending action for each access group.
        foreach ($accessGroups as $accessGroup) {

            // If already invited, skip
            if (isset($existingAccessGroups[$accessGroup->getGroupName()]))
                continue;

            $invitationCode = $this->pendingActionService->createPendingAction("OBJECT_SHARING_INVITE", $objectPrimaryKey, $accessGroup, "P7D", null, $objectClassName);

            // Send email using email template
            foreach ($accessGroup->getScopeAccesses() as $scopeAccess) {
                if ($scopeAccess->getScope() == Role::SCOPE_ACCOUNT)
                    $this->emailService->send(new AccountTemplatedEmail($scopeAccess->getItemIdentifier(), $emailTemplate, [
                        "sharable" => $sharable, "invitationCode" => $invitationCode, "loggedInUser" => $loggedInUser, "loggedInAccount" => $loggedInAccount
                    ]));
            }
        }


    }


    /**
     * Get a sharable object for a supplied invitation code
     *
     * @param $invitationCode
     * @return SharableItem
     *
     * @objectInterceptorDisabled
     */
    public function getSharableItemForInvitationCode($invitationCode) {

        // Pending action
        $pendingAction = $this->pendingActionService->getPendingActionByIdentifier("OBJECT_SHARING_INVITE", $invitationCode);

        $sharable = $this->orm->fetch($pendingAction->getObjectType(), $pendingAction->getObjectId());
        return new SharableItem($sharable);

    }


    /**
     * Accept an account invitation to share an object
     *
     * @param $invitationCode
     * @return void
     *
     * @objectInterceptorDisabled
     */
    public function acceptAccountInvitationToShareObject($invitationCode) {

        // Pending action
        $pendingAction = $this->pendingActionService->getPendingActionByIdentifier("OBJECT_SHARING_INVITE", $invitationCode);

        // Get scope access group
        $scopeAccessGroup = $this->objectBinder->bindFromArray($pendingAction->getData(), ScopeAccessGroup::class);

        foreach ($scopeAccessGroup->getScopeAccesses() as $objectScopeItem) {
            $objectScopeAccess = new ObjectScopeAccess($objectScopeItem->getScope(), $objectScopeItem->getItemIdentifier(), $scopeAccessGroup->getGroupName(), $scopeAccessGroup->getWriteAccess(), $scopeAccessGroup->getGrantAccess(), $scopeAccessGroup->getExpiryDate(), $pendingAction->getObjectType(), $pendingAction->getObjectId());
            $objectScopeAccess->save();
        }

        // Remove pending action once completed
        $this->pendingActionService->removePendingAction("OBJECT_SHARING_INVITE", $invitationCode);
    }


    /**
     * Reject an account invitation to share an object
     *
     * @param $invitationCode
     * @return void
     *
     * @objectInterceptorDisabled
     */
    public function rejectAccountInvitationToShareObject($invitationCode) {
        $this->pendingActionService->removePendingAction("OBJECT_SHARING_INVITE", $invitationCode);
    }


    /**
     * Cancel account invitations for access groups.
     *
     * @param string $objectClassName
     * @param string $objectPrimaryKey
     * @param string[] $accessGroups
     *
     * @return void
     */
    public function cancelAccountInvitationsForAccessGroups(string $objectClassName, string $objectPrimaryKey,
                                                            array  $accessGroups) {

        // Check the class being assigned is sharable
        $this->checkObjectSharableByLoggedInUser($objectClassName, $objectPrimaryKey);


        // Grab invitations
        $invitations = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("OBJECT_SHARING_INVITE", $objectPrimaryKey, $objectClassName);

        // Remove action if matching invite for group
        foreach ($invitations as $invitation) {
            if (in_array($invitation->getData()["groupName"] ?? null, $accessGroups))
                $this->pendingActionService->removePendingAction("OBJECT_SHARING_INVITE", $invitation->getIdentifier());
        }


    }


    /**
     * List invitation scope access groups for a shared object
     *
     * @param string $objectClassName
     * @param string $objectPrimaryKey
     *
     * @return ScopeAccessGroup[]
     *
     * @objectInterceptorDisabled
     */
    public function listInvitationsForSharedObject(string $objectClassName, string $objectPrimaryKey) {

        // Check the class being assigned is sharable
        $this->checkObjectSharableByLoggedInUser($objectClassName, $objectPrimaryKey);

        // Grab invitations
        $invitations = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("OBJECT_SHARING_INVITE", $objectPrimaryKey, $objectClassName);

        $scopeGroups = array_map(function ($item) {
            return $this->objectBinder->bindFromArray($item->getData(), ScopeAccessGroup::class);
        }, $invitations);

        // Add descriptions
        $this->addScopeObjectDescriptionsToScopeGroups($scopeGroups);

        return $scopeGroups;


    }


    /**
     * @param string $objectClassName
     * @param string $objectPrimaryKey
     * @return Sharable
     * @throws NoObjectGrantAccessException
     * @throws ObjectNotSharableException
     * @throws \Kinikit\Persistence\ORM\Exception\ObjectNotFoundException
     * @throws \ReflectionException
     */
    private function checkObjectSharableByLoggedInUser(string $objectClassName, string $objectPrimaryKey): mixed {


        // Check that the class being assigned is sharable
        $classInspector = new ClassInspector($objectClassName);
        if (!$classInspector->usesTrait(Sharable::class))
            throw new ObjectNotSharableException($objectClassName);

        // Grab the object using the ORM and ensure we have object grant access
        $object = $this->orm->fetch($objectClassName, $objectPrimaryKey);
        if (!$this->securityService->checkLoggedInObjectAccess($object, SecurityService::ACCESS_GRANT))
            throw new NoObjectGrantAccessException($objectClassName, $objectPrimaryKey);

        return $object;

    }


    private function addScopeObjectDescriptionsToScopeGroups($scopeGroups) {

        $scopedItems = [];
        foreach ($scopeGroups as $group) {
            // Group all items by scope
            $scopedItems = ArrayUtils::mergeArrayRecursive($scopedItems, ObjectArrayUtils::groupArrayOfObjectsByMember(["scope", "itemIdentifier"], $group->getScopeAccesses()));
        }


        // Now efficiently, obtain the scope descriptions
        foreach ($scopedItems as $scope => $items) {

            // Grab the scope access object
            $scopeAccess = $this->scopeManager->getScopeAccess($scope);

            // Resolve matching descriptions for passed ids
            $matchingDescriptions = $scopeAccess->getScopeObjectDescriptionsById(array_keys($items));

            $scopedItems[$scope] = ["items" => $matchingDescriptions, "label" => $scopeAccess->getScopeDescription()];

        }


        foreach ($scopeGroups as $scopeGroup) {
            foreach ($scopeGroup->getScopeAccesses() as $scopeAccess) {
                $scopeAccess->setScopeLabel($scopedItems[$scopeAccess->getScope()]["label"] ?? null);
                $scopeAccess->setItemLabel($scopedItems[$scopeAccess->getScope()]["items"][$scopeAccess->getItemIdentifier()] ?? null);
            }
        }
    }

}