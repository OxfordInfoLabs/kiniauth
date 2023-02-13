<?php

namespace Kiniauth\Services\Workflow;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Workflow\PendingAction;
use Kinikit\Core\Exception\ItemNotFoundException;

class PendingActionService {

    /**
     * Create a pending action for an account.  Returns the unique auto generated identifier for this action.
     *
     * @param $type
     * @param integer $objectId
     * @param mixed $data
     * @param string $expiryOffset
     * @param \DateTime $expiryDateTime
     * @param string $accountId
     *
     * @return string
     */
    public function createPendingAction($type, $objectId = null, $data = null, $expiryOffset = null, $expiryDateTime = null, $objectType = null) {

        $action = new PendingAction($type, $objectId, $data, $expiryOffset, $expiryDateTime, $objectType);
        $action->save();

        return $action->getIdentifier();

    }


    /**
     * Get a pending action by type and it's unique identifier
     *
     * @param string $type
     * @param string $identifier
     *
     * @return PendingAction
     */
    public function getPendingActionByIdentifier($type, $identifier) {

        $pendingActions = PendingAction::filter("WHERE type = ? AND identifier = ?", $type, $identifier);
        if (sizeof($pendingActions) > 0) {
            return $pendingActions[0];
        } else {
            throw new ItemNotFoundException("The pending action does not exist with the passed identifier");
        }

    }


    /**
     * Get all pending actions for a given type
     *
     * @param string $type
     *
     * @return PendingAction[]
     */
    public function getAllPendingActionsForType($type) {
        return PendingAction::filter("WHERE type = ? ORDER BY id DESC", $type);
    }


    /**
     * Get all account pending actions for a given type and object id.  If the object type is supplied
     * this will also be added to the limit.
     *
     * @param $type
     * @param integer $objectId
     *
     * @return PendingAction[]
     */
    public function getAllPendingActionsForTypeAndObjectId($type, $objectId, $objectType = null) {

        if ($objectType)
            return PendingAction::filter("WHERE type = ?  AND objectId = ? AND objectType = ? ORDER BY id DESC", $type, $objectId, $objectType);
        else
            return PendingAction::filter("WHERE type = ?  AND objectId = ? ORDER BY id DESC", $type, $objectId);

    }

    /**
     * Remove a pending action by identifier
     *
     * @param $resetCode
     */
    public function removePendingAction($type, $identifier) {
        $action = $this->getPendingActionByIdentifier($type, $identifier);
        $action->remove();
    }


    /**
     * Remove all pending actions for a specific type and object id (optionally a type).
     *
     * @param $type
     * @param $objectId
     * @param null $objectType
     */
    public function removeAllPendingActionsForTypeAndObjectId($type, $objectId, $objectType = null) {
        $matches = $this->getAllPendingActionsForTypeAndObjectId($type, $objectId, $objectType);
        foreach ($matches as $match) {
            $match->remove();
        }
    }

}
