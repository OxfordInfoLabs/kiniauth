<?php


namespace Kiniauth\Services\Communication\Messaging;


use Exception;
use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Objects\Communication\Messaging\Message;
use Kiniauth\Objects\Communication\Messaging\MessageSummary;
use Kiniauth\Objects\Communication\Messaging\MessageThread;
use Kiniauth\Objects\Communication\Messaging\MessageThreadSummary;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kinikit\Core\Configuration\Configuration;


/**
 * Service for sending and querying for sent messages.
 *
 */
class MessagingService {

    /**
     */
    public function __construct() {
    }


    /**
     * Get a message summary by id
     *
     * @param int $id
     *
     * @return MessageSummary
     */
    public function getMessageSummaryById($id) {
        return Message::fetch($id)->returnSummary();
    }

    /**
     * Save a new message
     *
     * @param MessageSummary $messageSummary
     *
     * @return void
     * @throws Exception
     *
     * @objectInterceptorDisabled
     */
    public function saveMessage($messageSummary): void {

        $threadId = $messageSummary->getMessageThreadId() ?? null;

        if (!$threadId) {
            throw new Exception("Message thread id is required to save a message");
        }

        $messageThread = $this->getMessageThreadSummaryById($threadId);

        $senderUserId = $messageSummary->getSenderUserId() ?? null;
        $receiverUserId = $messageSummary->getReceiverUserId() ?? null;
        $receiverAccountId = $messageSummary->getReceiverAccountId() ?? null;
        $receiverGroupId = $messageSummary->getReceiverGroupId() ?? null;

        // get all the users associated with the message
        $users = $this->getAllValidUsers(
            senderUserId: $senderUserId,
            receiverUserId: $receiverUserId,
            receiverAccountId: $receiverAccountId,
            receiverGroupId: $receiverGroupId
        );

        if (empty($users)) {
            throw new Exception("No valid users present for saving message");
        }

        // verify that the details in the message and the message thread are consistent
        $this->verifyThreadAndMessageConsistency(
            senderUserId: $senderUserId,
            receiverUserId: $receiverUserId,
            receiverAccountId: $receiverAccountId,
            receiverGroupId: $receiverGroupId,
            messageThread: $messageThread,
        );

        // we calculate the message id by incrementing the message count of the message thread
        $messageId = $messageThread->getMessageCount() + 1;

        // ensure all messages we create have the same date
        $messageDate = date("Y-m-d H:i:s");

        foreach ($users as $user) {

            $personalEncryptionKey = $user->getPersonalEncryptionKey();

            if (!$personalEncryptionKey) {
                throw new Exception("User {$user->getId()} does not have a personal encryption key");
            }

            $encryptedMessage = $this->encryptMessage(
                message: $messageSummary->getEncryptedMessage(),
                personalEncryptionKey: $personalEncryptionKey
            );

            $recipientMessage = new Message(
                new MessageSummary(
                    messageThreadId: $messageSummary->getMessageThreadId(),
                    encryptedMessage: $encryptedMessage,
                    messageType: $messageSummary->getMessageType(),
                    senderUserId: $messageSummary->getSenderUserId(),
                    senderAccountId: $messageSummary->getSenderAccountId(),
                    receiverUserId: $user->getId(),
                    receiverAccountId: $messageSummary->getReceiverAccountId(),
                    receiverGroupId: $messageSummary->getReceiverGroupId(),
                    messageDate: $messageDate,
                    messageId: $messageId
                )
            );
            $recipientMessage->save();
        }

        // save the updated message count to the message thread
        $messageThread->setMessageCount($messageId);
        $messageThread->save();
    }

    /**
     * Retrieves a list of unique and active user based on the hierarchy of recipient parameters
     * Higher-level parameters take precedence over lower-level ones when resolving user IDs.
     *
     * @param ?int $senderUserId
     * @param ?int $receiverUserId
     * @param ?int $receiverAccountId
     * @param ?int $receiverGroupId
     *
     * @return array
     * @throws Exception
     */
    private function getAllValidUsers(
        ?int $senderUserId,
        ?int $receiverUserId,
        ?int $receiverAccountId,
        ?int $receiverGroupId
    ): array {

        $userIds = [];

        // If a higher-level recipient is present, lower-level recipient IDs are ignored
        if ($receiverGroupId) {

            // Get all accounts belonging to the receiver group
            $accountIds = AccountGroupMember::values(
                "member_account_id",
                "WHERE account_group_id = ?",
                [$receiverGroupId]
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
                    [Role::SCOPE_ACCOUNT, ...$accountIds]
                );

                // check to see if the sender is in the groups valid users list
                if (!in_array($senderUserId, $userIds)) {
                    throw new exception("Sender user (ID: {$senderUserId}) is not a member of the group (ID: {$receiverGroupId})");
                }
            }
        }
        elseif ($receiverAccountId) {

            // resolve the account directly
            $userIds = UserRole::values(
                "scope_id",
                "WHERE account_id = ? AND scope = ?",
                [$receiverAccountId, Role::SCOPE_ACCOUNT]
            );

            // check to see if the sender is in the accounts valid users list
            if (!in_array($senderUserId, $userIds)) {
                throw new exception("Sender user (ID: {$senderUserId}) is not a member of the account (ID: {$receiverAccountId})");
            }
        }
        elseif ($receiverUserId) {

            // resolve the individual user directly
            $userIds = [$receiverUserId];
        }

        // add the sender user ID so sender can see their own messages
        $userIds[] = $senderUserId;


        // Remove duplicate user IDs before doing the final user lookup
        $userIds = array_values(array_unique($userIds));

        // Only retain active users
        if (!empty($userIds)) {

            $userPlaceholders = implode(
                ',',
                array_fill(0, count($userIds), '?')
            );

            return User::filter(
                "WHERE status = 'ACTIVE' AND id IN ($userPlaceholders)",
                [...$userIds]
            );
        }

        return [];
    }

    /**
     * @throws Exception
     */
    private function verifyThreadAndMessageConsistency(
        ?int $senderUserId,
        ?int $receiverUserId,
        ?int $receiverAccountId,
        ?int $receiverGroupId,
        MessageThreadSummary $messageThread
    ):void {

        // handling group id based consistency
        if (isset ($receiverGroupId)) {
            if ($messageThread->getMessageThreadGroupId() !== $receiverGroupId) {
                throw new Exception('Receiver group id does not match thread group id.');
            }
        }

        // handling account id based consistency
        if (isset ($receiverAccountId)) {
            if ($messageThread->getMessageThreadAccountId() !== $receiverAccountId) {
                throw new Exception('Receiver account id does not match thread account id.');
            }
        }


        // handling user id based consistency
        elseif (isset($receiverUserId)) {
            if ($messageThread->getMessageThreadUserId1() !== $senderUserId && $messageThread->getMessageThreadUserId2() !== $senderUserId) {
                throw new Exception('Sender user id does not match thread user ids.');
            }

            if ($messageThread->getMessageThreadUserId1() !== $receiverUserId && $messageThread->getMessageThreadUserId2() !== $receiverUserId) {
                throw new Exception('Receiver user id does not match thread user ids.');
            }
        }
    }

    /**
     * Encrypt a message for a single recipient
     *
     * @param string $message
     * @param string $personalEncryptionKey
     *
     * @return string
     * @throws Exception
     */
    private function encryptMessage(string $message, string $personalEncryptionKey): string {

        $applicationSecret = Configuration::readParameter("message.encryption.key");

        if (!function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
            throw new Exception('Sodium is required for message encryption.');
        }

        /*
         * Derive a unique 256-bit encryption key from:
         *
         * - the user's personal encryption key
         * - the config application secret key
         */
        $derivedKey = hash_hkdf(
            'sha256',
            $personalEncryptionKey,
            32,
            'kiniauth-message-encryption',
            $applicationSecret
        );

        // every encrypted message needs its own random nonce
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $message,
            '',
            $nonce,
            $derivedKey
        );

        // Store nonce + ciphertext together because the nonce is required for decryption
        return base64_encode($nonce . $ciphertext);
    }

    /**
     * Decrypt a message for a single recipient
     *
     * @param string $encryptedMessage
     * @param string $personalEncryptionKey
     *
     * @return string
     * @throws Exception
     */
    private function decryptMessage(string $encryptedMessage, string $personalEncryptionKey): string {

        $applicationSecret = Configuration::readParameter("message.encryption.key");

        if (!function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt')) {
            throw new Exception('Sodium is required for message decryption.');
        }

        $decodedMessage = base64_decode($encryptedMessage, true);

        if ($decodedMessage === false) {
            throw new Exception('Invalid encrypted message format.');
        }

        $nonceLength = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;

        if (strlen($decodedMessage) <= $nonceLength) {
            throw new Exception('Invalid encrypted message: missing ciphertext.');
        }

        $nonce = substr($decodedMessage, 0, $nonceLength);
        $ciphertext = substr($decodedMessage, $nonceLength);

        $derivedKey = hash_hkdf(
            'sha256',
            $personalEncryptionKey,
            32,
            'kiniauth-message-encryption',
            $applicationSecret
        );

        $decryptedMessage = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext,
            '',
            $nonce,
            $derivedKey
        );

        if ($decryptedMessage === false) {
            throw new Exception(
                'Unable to decrypt message.'
            );
        }

        return $decryptedMessage;
    }

    /**
     * Get all messages from a thread
     *
     * @param int $threadId
     * @param int $userId
     *
     * @return array
     * @throws Exception
     */
    public function getAllMessagesFromThread($threadId, $userId = User::LOGGED_IN_USER): array {

        $query = "WHERE messageThreadId = ? AND receiverUserId = ? ORDER BY id DESC";

        $results = Message::filter($query, [$threadId, $userId]);

        if (empty($results)) {
            throw new Exception("No messages found for user (ID: ({$userId}) in thread (ID: ({$threadId}).");
        }

        $user = User::filter(
            "WHERE status = 'ACTIVE' AND id = ?",
            [$userId]
        );

        if (empty($user)) {
            throw new Exception("User data (ID: {$userId}) not found in user table.");
        }

        $decryptedResults = [];

        foreach ($results as $result) {
            $summary = $result->returnSummary();

            $encryptedMessage = $summary->getEncryptedMessage();

            if ($encryptedMessage !== null) {
                $summary->setEncryptedMessage(
                    $this->decryptMessage(
                        encryptedMessage: $encryptedMessage,
                        personalEncryptionKey: $user[0]->getPersonalEncryptionKey()
                    )
                );
            }

            $decryptedResults[] = $summary;
        }

        return $decryptedResults;
    }

    /**
     * Get the latest message from a thead
     *
     * @param int $threadId
     * @param int $userId
     *
     * @returns MessageSummary
     * @throws Exception
     */
    public function getLatestMessageFromThread($threadId, $userId = User::LOGGED_IN_USER): ?MessageSummary {

        $query = "WHERE messageThreadId = ? AND receiverUserId = ? ORDER BY id DESC LIMIT 1";

        $results = Message::filter($query, [$threadId, $userId]);

        if (empty($results)) {
            throw new Exception("No messages found for user (ID: {$userId}) in thread (ID: {$threadId}).");
        }

        $user = User::filter(
            "WHERE status = 'ACTIVE' AND id = ?",
            [$userId]
        );

        if (empty($user)) {
            throw new Exception("User data (ID: {$userId}) not found in user table.");
        }

        $summary = $results[0]->returnSummary();

        $encryptedMessage = $results[0]->getEncryptedMessage();

        if ($encryptedMessage !== null) {
            $summary->setEncryptedMessage(
                $this->decryptMessage(
                    encryptedMessage: $encryptedMessage,
                    personalEncryptionKey: $user[0]->getPersonalEncryptionKey()
                )
            );
        }

        return $summary;
    }

    /**
     * Delete a message for a specific user in a message thread
     *
     * We retain the message object but remove the encrypted message content.
     *
     * @param int $threadId
     * @param int $messageId
     * @param int $userId
     *
     * @return void
     */
    public function deleteMessageForUser($threadId, $messageId, $userId = User::LOGGED_IN_USER): void {
        $messages = Message::filter(
            "WHERE messageThreadId = ? AND messageId = ? AND receiverUserId = ?",
            [$threadId, $messageId, $userId]
        );

        foreach ($messages as $message) {
            $message->setEncryptedMessage(null);
            $message->save();
        }
    }

    /**
     * Delete a message for all users in a message thread
     *
     * We retain the message object but remove the encrypted message content.
     *
     * @param int $threadId
     * @param int $messageId
     *
     * @return void
     */
    public function deleteMessageForAllUsers($threadId, $messageId): void {
        $messages = Message::filter(
            "WHERE messageThreadId = ? AND messageId = ?",
            [$threadId, $messageId]
        );

        foreach ($messages as $message) {
            $message->setEncryptedMessage(null);
            $message->save();
        }
    }



    /**
     * Fetch an existing message thread and return a summary
     *
     * @param int $messageThreadId
     *
     * @return MessageThreadSummary
     */
    public function getMessageThreadSummaryById($messageThreadId): MessageThreadSummary {
        return MessageThread::fetch($messageThreadId)->returnSummary();
    }

    /**
     * Fetch an existing message thread object
     *
     * @param int $messageThreadId
     *
     * @return MessageThread
     */
    public function getMessageThreadById($messageThreadId): MessageThread {
        return MessageThread::fetch($messageThreadId);
    }

    /**
     * Fetch all existing message threads at the user level
     *
     * @param int $messageThreadUserId
     *
     * @return array
     */
    public function getAllMessageThreadsByUserId($messageThreadUserId): array {

        $query = "WHERE messageThreadUserId1 = ? OR messageThreadUserId2 = ?";

        return MessageThread::filter($query, [$messageThreadUserId, $messageThreadUserId]);
    }

    /**
     * Fetch all existing message threads at the account level
     *
     * @param int $messageThreadAccountId
     *
     * @return array
     */
    public function getAllMessageThreadsByAccountId($messageThreadAccountId): array {

        $query = "WHERE messageThreadAccountId = ?";

        return MessageThread::filter($query, [$messageThreadAccountId]);
    }

    /**
     * Fetch all existing message threads at the group level
     *
     * @param int $messageThreadGroupId
     *
     * @return array
     */
    public function getAllMessageThreadsByGroupId($messageThreadGroupId): array {

        $query = "WHERE messageThreadGroupId = ?";

        return MessageThread::filter($query, [$messageThreadGroupId]);
    }


    /**
     * Save a new message thread
     *
     * @param MessageThreadSummary $messageThreadSummary
     *
     * @return int
     */
    public function saveMessageThread($messageThreadSummary): int {

        $messageThread = new MessageThread($messageThreadSummary);
        $messageThread->save();

        return $messageThread->getId();
    }

    /**
     * Delete an existing message thread
     *
     * @param int $messageThreadId
     *
     * @return void
     */
    public function deleteMessageThread($messageThreadId): void {
        $messageThreadSummary = $this->getMessageThreadById($messageThreadId);
        $messageThreadSummary->remove();
    }
}
