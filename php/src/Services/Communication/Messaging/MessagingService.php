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
     * @return int
     * @throws Exception
     */
    public function saveMessage($messageSummary): int {

        $receiverUserId = $messageSummary->getReceiverUserId() ?? null;
        $receiverAccountId = $messageSummary->getReceiverAccountId() ?? null;
        $receiverGroupId = $messageSummary->getReceiverGroupId() ?? null;

        $users = $this->getAllValidUsers($receiverUserId, $receiverAccountId, $receiverGroupId);

        if (empty($users)) {
            return -1;
        }

        foreach ($users as $user) {

            $personalEncryptionKey = $user->getPersonalEncryptionKey();

            if (!$personalEncryptionKey) {
                throw new Exception("User {$user->getId()} does not have a personal encryption key");
            }

            $encryptedMessage = $this->encryptMessage(
                $messageSummary->getEncryptedMessage(),
                $personalEncryptionKey
            );

            $recipientMessage = new Message(
                new MessageSummary(
                    $messageSummary->getMessageThreadId(),
                    $encryptedMessage,
                    $messageSummary->getMessageType(),
                    $messageSummary->getSenderUserId(),
                    $messageSummary->getSenderAccountId(),
                    $user->getId(),
                    $messageSummary->getReceiverAccountId(),
                    $messageSummary->getReceiverGroupId(),
                    $messageSummary->getMessageDate()
                )
            );
            $recipientMessage->save();
        }

        return 0;
    }

    /**
     * Retrieves a list of unique and active user based on the hierarchy of recipient parameters
     * Higher-level parameters take precedence over lower-level ones when resolving user IDs.
     *
     * @param ?int $receiverUserId
     * @param ?int $receiverAccountId
     * @param ?int $receiverGroupId
     *
     * @return array
     */
    private function getAllValidUsers(?int $receiverUserId, ?int $receiverAccountId, ?int $receiverGroupId): array {

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

        // Remove duplicate user IDs before doing the final user lookup
        $userIds = array_values(array_unique($userIds));

        // Only retain active users
        if (!empty($userIds)) {

            $userPlaceholders = implode(
                ',',
                array_fill(0, count($userIds), '?')
            );

            $userIds = User::filter(
                "WHERE status = 'ACTIVE' AND id IN ($userPlaceholders)",
                [...$userIds]
            );
        }

        // final deduplication
        return array_values(array_unique($userIds));
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
    public function getAllMessagesFromThread($threadId, $userId): array {

        $query = "WHERE messageThreadId = ? AND userId = ? ORDER BY id DESC";

        $results = Message::filter($query, [$threadId, $userId]);

        if (empty($results)) {
            throw new Exception("No messages found for user (ID: ({$userId}) in thread (ID: ({$threadId}).");
        }

        $user = User::filter(
            "WHERE status = 'ACTIVE' AND id = ?)",
            [$userId]
        );

        if (empty($user)) {
            throw new Exception("User data (ID: {$userId}) not found in user table.");
        }

        return array_map(function ($item) {

            $summary = $item->returnSummary();

            $summary->setEncryptedMessage(
                $this->decryptMessage(
                    $item->getEncryptedMessage(),
                    //key-here
                )
            );

            return $summary;
        }, $results);
    }

    /**
     * Get the latest message from a thead
     *
     * @param int $threadId
     * @param int $userId
     *
     * @returns MessageSummary
     *
     */
    public function getLatestMessageFromThread($threadId, $userId): ?MessageSummary {

        $query = "WHERE messageThreadId = ? AND userId = ? ORDER BY id DESC LIMIT 1";

        $results = Message::filter($query, [$threadId, $userId]);

        if (empty($results)) {
            throw new Exception("No messages found for user (ID: ({$userId}) in thread (ID: ({$threadId}).");
        }

        $user = User::filter(
            "WHERE status = 'ACTIVE' AND id = ?)",
            [$userId]
        );

        if (count($user) === 1) {
            throw new Exception("User data (ID: {$userId}) not found in user table.");
        }

        $summary = $results[0]->returnSummary();

        $summary->setEncryptedMessage(
            $this->decryptMessage(
                $results[0]->getEncryptedMessage(),
                $user[0]->getPersonalEncryptionKey()
            )
        );

        return $results[0]->returnSummary();
    }

    /**
     * @param $messageId
     *
     * @return void
     */
    public function deleteMesssage($messageId) {
        $messageSummary = $this->getMessageSummaryById($messageId);
        $messageSummary->remove();
    }



    /**
     * Fetch an existing message thread and return a summary
     *
     * @param $id
     *
     * @return MessageThreadSummary
     */
    public function getMessageThreadSummaryById($id) {
        return MessageThread::fetch($id)->returnSummary();
    }

    /**
     * Fetch all existing message threads at the user level
     *
     * @param $messageThreadUserId
     *
     * @return array
     */
    public function getMessageThreadByUserId($messageThreadUserId): array {

        $query = "WHERE messageThreadUserId = ?";

        $results = MessageThread::filter($query, [$messageThreadUserId]);
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
    public function getMessageThreadByAccountId($messageThreadAccountId): array {

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
    public function getMessageThreadByGroupId($messageThreadGroupId): array {

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
