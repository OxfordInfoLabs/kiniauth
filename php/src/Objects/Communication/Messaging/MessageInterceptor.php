<?php


namespace Kiniauth\Objects\Communication\Messaging;



use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;


/**
 * Interceptor for encryption and decryption of strings.
 *
 */
class MessageInterceptor extends DefaultORMInterceptor {

    public function __construct() {}

    public function preSave($object) {

        $receiverUserId = $object->getReceiverUserId();
        $receiverAccountId = $object->getReceiverAccountId();
        $receiverGroupId = $object->getReceiverGroupId();

        $userIds = [];


        // If a higher-level recipient is present, lower-level recipient IDs are ignored
        if ($receiverGroupId) {

            // Get all accounts belonging to the receiver group
            $accountIds = AccountGroupMember::values(
                "member_account_id",
                "WHERE account_group_id = ?",
                $receiverGroupId
            );

            // Get all users belonging to those accounts.
            if ($accountIds) {

                $accountIds = array_values(array_unique($accountIds));

                // Get all account-scoped users for all member accounts
                $accountPlaceholders = implode(
                    ',',
                    array_fill(0, count($accountIds), '?')
                );

                $userIds = UserRole::values(
                    "scope_id",
                    "WHERE scope = ? AND account_id IN ($accountPlaceholders)",
                    Role::SCOPE_ACCOUNT,
                    ...$accountIds,
                );
            }
        }
        elseif ($receiverAccountId) {

            // resolve the account directly
            $userIds = UserRole::values(
                "scope_id",
                "WHERE account_id = ? AND scope = ?",
                $receiverAccountId,
                Role::SCOPE_ACCOUNT
            );
        }
        elseif ($receiverUserId) {

            // resolve the individual user directly
            $userIds = [$receiverUserId];
        }

        // Remove duplicate user IDs before doing the final user lookup
        $userIds = array_values(array_unique($userIds));

        // Only retain active users
        if (!empty($userIds)) {

            $userPlaceholders = implode(
                ',',
                array_fill(0, count($userIds), '?')
            );

            $userIds = User::values(
                "id",
                "WHERE status = ? AND id IN ($userPlaceholders)",
                User::STATUS_ACTIVE,
                ...$userIds
            );
        }

        // final deduplication
        $userIds = array_values(array_unique($userIds));

        //print_r($userIds);

        //$message = $object->getEncryptedMessage();
    }



}
