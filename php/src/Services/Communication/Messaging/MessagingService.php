<?php


namespace Kiniauth\Services\Communication\Messaging;


use Exception;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Objects\Communication\Messaging\Message;
use Kiniauth\Objects\Communication\Messaging\MessageSummary;
use Kiniauth\Objects\Communication\Messaging\MessageThread;
use Kiniauth\Objects\Communication\Messaging\MessageThreadSummary;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Objects\Security\UserSummary;
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
        $this->validateMessageThreadAccess(
            thread: $messageThread,
            users: $users
        );

        // we calculate the message id by incrementing the message count of the message thread
        $messageId = $messageThread->getMessageCount() + 1;
        $messageThread->setMessageCount($messageId);
        $messageThread->save();

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
            }
        }
        elseif ($receiverAccountId) {

            // resolve the account directly
            $userIds = UserRole::values(
                "scope_id",
                "WHERE account_id = ? AND scope = ?",
                [$receiverAccountId, Role::SCOPE_ACCOUNT]
            );
        }
        elseif ($receiverUserId) {

            // resolve the individual user directly
            $userIds = [$receiverUserId];
        }

        if ($senderUserId) {
            $userIds[] = $senderUserId;
        }

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
     * Function to validate if the user ids associated with a message
     * are valid to the message thread its attempting to send to
     *
     * @param MessageThreadSummary $thread
     * @param array $users
     *
     * @return void
     * @throws Exception
     */
    private function validateMessageThreadAccess(
        MessageThreadSummary $thread,
        array $users
    ): void {

        $threadUserId1 = $thread->getMessageThreadUserId1();
        $threadUserId2 = $thread->getMessageThreadUserId2();
        $threadAccountId = $thread->getMessageThreadAccountId();
        $threadGroupId = $thread->getMessageThreadGroupId();

        if ($threadGroupId) {

            $accountIds = AccountGroupMember::values(
                "member_account_id",
                "WHERE account_group_id = ?",
                [$threadGroupId]
            );

            $allowedUserIds = [];

            if (!empty($accountIds)) {

                $accountIds = array_values(array_unique($accountIds));

                $accountPlaceholders = implode(
                    ',',
                    array_fill(0, count($accountIds), '?')
                );

                $allowedUserIds = UserRole::values(
                    "scope_id",
                    "WHERE scope = ? AND account_id IN ($accountPlaceholders)",
                    [Role::SCOPE_ACCOUNT, ...$accountIds]
                );
            }

        } elseif ($threadAccountId) {

            $allowedUserIds = UserRole::values(
                "scope_id",
                "WHERE account_id = ? AND scope = ?",
                [$threadAccountId, Role::SCOPE_ACCOUNT]
            );

        } elseif ($threadUserId1 || $threadUserId2) {

            $allowedUserIds = [$threadUserId1, $threadUserId2];

        } else {
            throw new Exception("Message thread has no valid access scope");
        }

        $allowedUserIds = array_map(
            'intval',
            array_unique($allowedUserIds)
        );

        foreach ($users as $user) {

            if (!in_array((int) $user->getId(), $allowedUserIds, true)) {
                throw new Exception(
                    "User {$user->getId()} does not have access to message thread {$thread->getId()}"
                );
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
            throw new Exception("No messages found for user (ID: ({$userId}) in thread (ID: ({$threadId}).");
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
     * @param $threadId
     * @param $messageId
     * @param $userId
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
     * @param $threadId
     * @param $messageId
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
     * @param $messageThreadId
     *
     * @return MessageThreadSummary
     */
    public function getMessageThreadSummaryById($messageThreadId): MessageThreadSummary {
        return MessageThread::fetch($messageThreadId)->returnSummary();
    }

    /**
     * Fetch all existing message threads at the user level
     *
     * @param $messageThreadUserId
     *
     * @return array
     */
    public function getAllMessageThreadsByUserId($messageThreadUserId): array {

        $query = "WHERE messageThreadUserId1 = ? OR messageThreadUserId2 = ?";

        $results = MessageThread::filter($query, [$messageThreadUserId, $messageThreadUserId]);
        return array_map(function ($item) {
            return $item->returnSummary();
        }, $results);
    }

    /**
     * Fetch all existing message threads at the account level
     *
     * @param $messageThreadAccountId
     *
     * @return array
     */
    public function getAllMessageThreadsByAccountId($messageThreadAccountId): array {

        $query = "WHERE messageThreadAccountId = ?";

        $results = MessageThread::filter($query, [$messageThreadAccountId]);
        return array_map(function ($item) {
            return $item->returnSummary();
        }, $results);
    }

    /**
     * Fetch all existing message threads at the group level
     *
     * @param $messageThreadGroupId
     *
     * @return array
     */
    public function getAllMessageThreadsByGroupId($messageThreadGroupId): array {

        $query = "WHERE messageThreadGroupId = ?";

        $results = MessageThread::filter($query, [$messageThreadGroupId]);
        return array_map(function ($item) {
            return $item->returnSummary();
        }, $results);
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
    public function deleteMessageThread($messageThreadId) {
        $messageThreadSummary = $this->getMessageThreadSummaryById($messageThreadId);
        $messageThreadSummary->remove();
    }
}
