<?php


namespace Kiniauth\Services\Communication\Messaging;


use Exception;
use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Objects\Communication\Messaging\Message;
use Kiniauth\Objects\Communication\Messaging\MessageReaction;
use Kiniauth\Objects\Communication\Messaging\MessageReactionSummary;
use Kiniauth\Objects\Communication\Messaging\MessageSummary;
use Kiniauth\Objects\Communication\Messaging\MessageThread;
use Kiniauth\Objects\Communication\Messaging\MessageThreadSummary;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
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

    //
    // Messages
    //

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

        // make sure the message isn't empty
        if (
            $messageSummary->getEncryptedMessage() === null ||
            trim($messageSummary->getEncryptedMessage()) === ''
        ) {
            throw new Exception("New message cannot be empty");
        }

        $threadId = $messageSummary->getMessageThreadId();

        if (!$threadId) {
            throw new Exception("Message thread id is required to save a message");
        }

        try {
            $messageThread = $this->getMessageThreadSummaryById($threadId);
        }
        catch (Exception $e) {
            throw new Exception("Message thread (ID: {$threadId}) cannot be found");
        }

        $senderUserId = $messageSummary->getSenderUserId();
        $receiverUserId = $messageSummary->getReceiverUserId();
        $receiverAccountId = $messageSummary->getReceiverAccountId();
        $receiverGroupId = $messageSummary->getReceiverGroupId();

        // verify that the details in the message and the message thread are consistent
        $this->verifyThreadAndMessageConsistency(
            senderUserId: $senderUserId,
            receiverUserId: $receiverUserId,
            receiverAccountId: $receiverAccountId,
            receiverGroupId: $receiverGroupId,
            messageThread: $messageThread,
        );

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

        // we generate a random positive message ID that is not already used in this thread
        $messageId = $this->generateUniqueMessageId($threadId);

        // ensure all messages we create have the same date
        $messageDate = date("Y-m-d H:i:s");

        // save recipient messages to ensure everything is good before saving
        $recipientMessages = [];

        foreach ($users as $user) {

            $personalEncryptionKey = $user->getPersonalEncryptionKey();

            if (!$personalEncryptionKey) {
                throw new Exception("User (ID: {$user->getId()}) does not have a personal encryption key");
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
                    senderUserName: $messageSummary->getSenderUserName(),
                    senderAccountId: $messageSummary->getSenderAccountId(),
                    receiverUserId: $user->getId(),
                    receiverAccountId: $messageSummary->getReceiverAccountId(),
                    receiverGroupId: $messageSummary->getReceiverGroupId(),
                    messageDate: $messageDate,
                    messageId: $messageId
                )
            );

            // mark the message as read for the sender user
            if ($user->getId() === $messageSummary->getSenderUserId()) {
                $recipientMessage->setMessageRead(true);
            }

            $recipientMessages[] = $recipientMessage;
        }

        // save all recipient messages
        foreach ($recipientMessages as $recipientMessage) {
            $recipientMessage->save();
        }
    }

    /**
     * Generate a random positive message ID that is not already used in a thread.
     *
     * @param int $threadId
     *
     * @return int
     * @throws Exception
     */
    private function generateUniqueMessageId(int $threadId): int {

        do {
            $messageId = random_int(1, PHP_INT_MAX);

            $existingMessages = Message::filter(
                "WHERE messageThreadId = ? AND messageId = ?",
                [$threadId, $messageId]
            );

        } while (!empty($existingMessages));

        return $messageId;
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

                $accountUsers = User::filter(
                    "WHERE roles.scope = ? AND roles.account_id IN ($accountPlaceholders)",
                    [Role::SCOPE_ACCOUNT, ...$accountIds]
                );

                $userIds = array_values(array_unique(array_map(
                    fn($user) => $user->getId(),
                    $accountUsers
                )));

                // check to see if the sender is in the groups valid users list
                if (!in_array($senderUserId, $userIds)) {
                    throw new exception("Sender user (ID: {$senderUserId}) is not a member of the group (ID: {$receiverGroupId})");
                }
            }
            else {
                throw new exception("Sender user (ID: {$senderUserId}) is not a member of the group (ID: {$receiverGroupId})");
            }
        }
        elseif ($receiverAccountId) {

            // resolve the account directly
            $accountUsers = User::filter(
                "WHERE roles.scope = ? AND roles.account_id = ?",
                [Role::SCOPE_ACCOUNT, $receiverAccountId]
            );

            $userIds = array_values(array_unique(array_map(
                fn($user) => $user->getId(),
                $accountUsers
            )));

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

        if (!$receiverUserId && !$receiverAccountId && !$receiverGroupId) {
            throw new Exception("At least one recipient parameter must be provided.");
        }

        // handling group id based consistency
        if ($messageThread->getMessageThreadGroupId() !== null) {
            if ($messageThread->getMessageThreadGroupId() !== $receiverGroupId) {
                throw new Exception('Receiver group id does not match thread group id.');
            }

            if (isset($receiverAccountId) || isset($receiverUserId)) {
                throw new Exception('Incoming group message contains unnecessary account or user data.');
            }
        }

        // handling account id based consistency
        else if ($messageThread->getMessageThreadAccountId() !== null) {
            if ($messageThread->getMessageThreadAccountId() !== $receiverAccountId) {
                throw new Exception('Receiver account id does not match thread account id.');
            }

            if (isset($receiverUserId) || isset($receiverGroupId)) {
                throw new Exception('Incoming account message contains unnecessary user or group data.');
            }
        }


        // handling user id based consistency
        else if ($messageThread->getMessageThreadUserId1() !== null && $messageThread->getMessageThreadUserId2() !== null) {
            if ($messageThread->getMessageThreadUserId1() !== $senderUserId && $messageThread->getMessageThreadUserId2() !== $senderUserId) {
                throw new Exception('Sender user id does not match thread user ids.');
            }

            if ($messageThread->getMessageThreadUserId1() !== $receiverUserId && $messageThread->getMessageThreadUserId2() !== $receiverUserId) {
                throw new Exception('Receiver user id does not match thread user ids.');
            }

            if (isset($receiverAccountId) || isset($receiverGroupId)) {
                throw new Exception('Incoming user message contains unnecessary account or group data.');
            }
        }

        else (
            throw new Exception("Message thread is malformed")
        );
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
     * Edit the encrypted message text of an existing message
     *
     * @param int $threadId
     * @param int $messageId
     * @param string $newMessage
     * @param int $userId
     *
     * @return void
     * @throws Exception
     */
    public function editMessage(
        int $threadId,
        int $messageId,
        string $newMessage,
        $userId = User::LOGGED_IN_USER
    ): void {

        // make sure the message isn't empty
        if (trim($newMessage) === '') {
            throw new Exception("New message cannot be empty");
        }

        // fetch all messages we want to edit
        $messages = Message::filter(
            "WHERE messageThreadId = ? AND messageId = ?",
            [$threadId, $messageId]
        );

        if (empty($messages)) {
            throw new Exception("Message (ID: {$messageId}) not found in thread (ID: {$threadId}).");
        }

        // only allow the user who sent the message to edit it
        if ($messages[0]->getSenderUserId() !== $userId) {
            throw new Exception("User (ID: {$userId}) is not the sender of the message (ID: {$messageId})."
                . " Only the sender can edit a message.");
        }

        $receiverKeys = [];

        // get all the personal encryption keys for all the recipients
        foreach ($messages as $message) {

            $receiverUserId = $message->getReceiverUserId();

            $user = User::filter(
                "WHERE status = 'ACTIVE' AND id = ?",
                [$receiverUserId]
            );

            if (empty($user)) {
                throw new Exception("User (ID: {$receiverUserId}) not found.");
            }

            $personalEncryptionKey = $user[0]->getPersonalEncryptionKey();

            if (!$personalEncryptionKey) {
                throw new Exception("User (ID: {$receiverUserId}) does not have a personal encryption key");
            }

            $receiverKeys[$receiverUserId] = $personalEncryptionKey;
        }

        // set a new message date for all messages
        $messageDate = date("Y-m-d H:i:s");

        foreach ($messages as $message) {
            
            // ignore deleted messages
            if ($message->getEncryptedMessage() === null) {
                continue;
            }

            // encrypt new message
            $encryptedMessage = $this->encryptMessage(
                message: $newMessage,
                personalEncryptionKey: $receiverKeys[$message->getReceiverUserId()]
            );

            // update message with new details
            $message->setEncryptedMessage($encryptedMessage);
            $message->setMessageDate($messageDate);
            $message->save();
        }
    }

    /**
     * Function to mark a message as read
     *
     * @param int $threadId
     * @param int $messageId
     * @param $userId
     *
     * @return void
     * @throws Exception
     */
    public function markMessageRead(int $threadId, int $messageId, $userId = User::LOGGED_IN_USER): void {
        $message = Message::filter("WHERE messageThreadId = ? AND messageId = ? AND receiverUserId = ?",
            [$threadId, $messageId, $userId]
        );

        if (empty($message)) {
            throw new Exception("Message (ID: {$messageId}) not found for user (ID: {$userId}) ".
                "in thread (ID: {$threadId}).");
        }

        $message[0]->setMessageRead(true);
        $message[0]->save();
    }

    /**
     * Get all messages from a thread
     *
     * Will include reactions
     *
     * @param int $threadId
     * @param int $userId
     *
     * @return array
     * @throws Exception
     */
    public function getAllMessagesAndReactionsFromThread($threadId, $userId = User::LOGGED_IN_USER): array {

        // check that the user is active
        $user = User::filter(
            "WHERE status = 'ACTIVE' AND id = ?",
            [$userId]
        );

        if (empty($user)) {
            throw new Exception("User (ID: {$userId}) not found in user table.");
        }

        // make sure the user has a personal encryption key
        if ($user[0]->getPersonalEncryptionKey() === null) {
            throw new Exception("User (ID: {$userId}) does not have a personal encryption key.");
        }

        // search for all messages in the thread
        $query = "WHERE messageThreadId = ? AND receiverUserId = ? ORDER BY id DESC";

        $results = Message::filter($query, [$threadId, $userId]);

        if (empty($results)) {
            return [];
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

            // Fetch all reactions for this message
            $reactions = MessageReaction::filter(
                "WHERE messageThreadId = ? AND messageId = ? ORDER BY id DESC",
                [$threadId, $summary->getMessageId()]
            );

            $reactionSummaries = array_map(
                fn($reaction) => $reaction->returnSummary(),
                $reactions
            );

            // Attach the reactions to the message summary
            $summary->setMessageReactions($reactionSummaries);

            $decryptedResults[] = $summary;
        }

        return $decryptedResults;
    }

    /**
     * Get the latest message from a thead
     *
     * Won't include reactions
     *
     * @param int $threadId
     * @param int $userId
     *
     * @returns MessageSummary
     * @throws Exception
     */
    public function getLatestMessageFromThread($threadId, $userId = User::LOGGED_IN_USER): ?MessageSummary {

        // check that the user is active
        $user = User::filter(
            "WHERE status = 'ACTIVE' AND id = ?",
            [$userId]
        );

        if (empty($user)) {
            throw new Exception("User (ID: {$userId}) not found in user table.");
        }

        // make sure the user has a personal encryption key
        if ($user[0]->getPersonalEncryptionKey() === null) {
            throw new Exception("User (ID: {$userId}) does not have a personal encryption key.");
        }

        // search for the latest message in the thread
        $query = "WHERE messageThreadId = ? AND receiverUserId = ? ORDER BY id DESC LIMIT 1";

        $results = Message::filter($query, [$threadId, $userId]);

        if (empty($results)) {
            return null;
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
     * @throws Exception
     */
    public function deleteMessageForUser($threadId, $messageId, $userId = User::LOGGED_IN_USER): void {
        $messages = Message::filter(
            "WHERE messageThreadId = ? AND messageId = ? AND receiverUserId = ?",
            [$threadId, $messageId, $userId]
        );

        if (empty($messages)) {
            throw new Exception("Message (ID: {$messageId}) not found in thread (ID: {$threadId})" .
            " for user (ID: {$userId})");
        }

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
     * @param int $userId
     *
     * @return void
     * @throws Exception
     */
    public function deleteMessageForAllUsers($threadId, $messageId, $userId = User::LOGGED_IN_USER): void {
        $messages = Message::filter(
            "WHERE messageThreadId = ? AND messageId = ?",
            [$threadId, $messageId]
        );

        if (empty($messages)) {
            throw new Exception("Message (ID: {$messageId}) not found in thread (ID: {$threadId})");
        }

        // only the sender of the message can delete the message for all users
        if ($messages[0]->getSenderUserId() !== $userId) {
            throw new Exception("Only the sender of the message can delete the message for everyone");
        }

        foreach ($messages as $message) {
            $message->setEncryptedMessage(null);
            $message->save();
        }
    }

    //
    // Reactions
    //

    /**
     * Function to give a reaction to a message
     *
     * @param MessageReactionSummary $messageReactionSummary
     *
     * @return void
     * @throws Exception
     */
    public function saveMessageReaction(MessageReactionSummary $messageReactionSummary): void {

        $reaction = $messageReactionSummary->getReaction();
        $threadId = $messageReactionSummary->getMessageThreadId();
        $messageId = $messageReactionSummary->getMessageId();
        $userId = $messageReactionSummary->getSenderUserId();
        $userName = $messageReactionSummary->getSenderUserName();

        if (trim($reaction) === "" || $reaction === null) {
            throw new Exception("Reaction cannot be empty");
        }

        // get all the messages
        $messages = Message::filter(
            "WHERE messageThreadId = ? AND messageId = ?",
            [$threadId, $messageId]
        );

        if (empty($messages)) {
            throw new Exception("Message (ID: {$messageId}) not found in thread (ID: {$threadId})");
        }

        // verify that the user is a recipient of the message
        $userMessage = array_values(array_filter(
            $messages,
            fn($message) => $message->getReceiverUserId() === $userId
        ));

        if (empty($userMessage)) {
            throw new Exception("User (ID: {$userId}) is not a recipient of message " .
                "(ID: {$messageId}) in thread (ID: {$threadId})"
            );
        }

        // check to see if this user has reacted already
        $existingReaction = MessageReaction::filter(
            "WHERE messageThreadId = ? AND messageId = ? AND senderUserId = ?",
            [$threadId, $messageId, $userId]
        );

        $reactionDate = date("Y-m-d H:i:s");

        if (!empty($existingReaction)) {

            // update the existing reaction with new data
            $existingReaction[0]->setReaction($reaction);
            $existingReaction[0]->setReactionDate($reactionDate);
            $existingReaction[0]->save();
        }
        else {
            // otherwise create a new reaction
            $reaction = new MessageReaction(
                new MessageReactionSummary(
                    messageId: $messageId,
                    messageThreadId:$threadId,
                    reaction: $reaction,
                    senderUserId: $userId,
                    senderUserName: $userName,
                    reactionDate: $reactionDate,
                )
            );
            $reaction->save();
        }
    }

    /**
     * Function to remove a reaction from a message
     *
     * @param int $threadId
     * @param int $messageId
     * @param int $userId
     *
     * @return void
     * @throws Exception
     */
    public function deleteMessageReaction(int $threadId, int $messageId, $userId = User::LOGGED_IN_USER): void {
        // verify that the user has access to this message
        $messages = Message::filter(
            "WHERE messageThreadId = ? AND messageId = ? AND receiverUserId = ?",
            [$threadId, $messageId, $userId]
        );

        if (empty($messages)) {
            throw new Exception("User (ID: {$userId}) is not a recipient of message " .
                "(ID: {$messageId}) in thread (ID: {$threadId})"
            );
        }

        // Fetch the users reaction for the message
        $reactions = MessageReaction::filter(
            "WHERE messageThreadId = ? AND messageId = ? AND senderUserId = ?",
            [$threadId, $messageId, $userId]
        );

        if (empty($reactions)) {
            throw new Exception("User (ID: {$userId}) has not reacted to message " .
                "(ID: {$messageId}) in thread (ID: {$threadId})"
            );
        }

        // Remove the reaction for the message from this user
        $reactions[0]->remove();
    }

    //
    // Message Threads
    //

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
     * @throws Exception
     */
    public function saveMessageThread($messageThreadSummary): int {

        $messageThreadUserId1 = $messageThreadSummary->getMessageThreadUserId1();
        $messageThreadUserId2 = $messageThreadSummary->getMessageThreadUserId2();
        $messageThreadAccountId = $messageThreadSummary->getMessageThreadAccountId();
        $messageThreadGroupId = $messageThreadSummary->getMessageThreadGroupId();

        // A thread can only be user-2-user, account based or group based, but not a combination of them
        $isUserToUser = $messageThreadUserId1 && $messageThreadUserId2 && !$messageThreadAccountId && !$messageThreadGroupId;
        $isAccountBased = !$messageThreadUserId1 && !$messageThreadUserId2 && $messageThreadAccountId && !$messageThreadGroupId;
        $isGroupBased = !$messageThreadUserId1 && !$messageThreadUserId2 && !$messageThreadAccountId && $messageThreadGroupId;

        if (!$isUserToUser && !$isAccountBased && !$isGroupBased) {
            throw new Exception("Message thread must be either user-2-user, account based, or group based.");
        }

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
        $messageThreadSummary = $this->getMessageThreadSummaryById($messageThreadId);
        $messageThreadSummary->remove();
    }
}
