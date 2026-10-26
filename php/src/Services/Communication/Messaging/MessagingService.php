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
use Kiniauth\Objects\Communication\Notification\Notification;
use Kiniauth\Objects\Communication\Notification\NotificationSummary;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserCommunicationData;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Communication\Notification\CommunicationMethod\EmailNotificationCommunicationMethod;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kinikit\Core\Configuration\Configuration;


/**
 * Service for sending and querying for sent messages.
 *
 */
class MessagingService {

    /**
     */
    public function __construct(
        private readonly NotificationService                    $notificationService,
        private readonly EmailNotificationCommunicationMethod   $communicationMethod
    ) {
    }

    //
    // Messages
    //


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

        $threadId = $messageSummary->getMessageThreadId();

        if (!$threadId) {
            throw new Exception("Message thread id is required to save a message");
        }

        $messageThread = $this->getMessageThreadSummaryById($threadId);

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

        // we calculate the message id by incrementing the message count of the message thread
        $messageId = $messageThread->getMessageCount() + 1;

        // ensure all messages we create have the same date
        $messageDate = date("Y-m-d H:i:s");

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
                    messageReactions: [],
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
            $recipientMessage->save();

            // send a notification to all recipients except the sender
            if ($user->getId() !== $messageSummary->getSenderUserId()) {

                // create a notification for each message we create
                $notificationId = $this->notificationService->createNotification(
                    new NotificationSummary(
                        "New Message Notification",
                        "You have received a new message from {$messageSummary->getSenderUsername()}" .
                        " in thread (ID: {$threadId}).",
                        new UserCommunicationData($user->getId())
                    )
                );

                // send an email of this notification to the recipient
//                $this->communicationMethod->processIndividualNotification(
//                    Notification::fetch($notificationId)
//                );
            }
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

        if (!$receiverUserId && !$receiverAccountId && !$receiverGroupId) {
            throw new Exception("At least one recipient parameter must be provided.");
        }

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

        // fetch all messages we want to edit
        $messages = Message::filter(
            "WHERE messageThreadId = ? AND messageId = ?",
            [$threadId, $messageId]
        );

        if (empty($messages)) {
            throw new Exception("Message (ID: {$messageId}) not found in thread (ID: {$threadId}).");
        }

        $receiverKeys = [];

        // only allow the user who sent the message to edit it
        if ($messages[0]->getSenderUserId() !== $userId) {
            throw new Exception("User (ID: {$userId}) is not the sender of the message (ID: {$messageId})."
                . " Only the sender can edit a message.");
        }

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
     * @throws Exception
     */
    public function deleteMessageForUser($threadId, $messageId, $userId = User::LOGGED_IN_USER): void {
        $messages = Message::filter(
            "WHERE messageThreadId = ? AND messageId = ? AND receiverUserId = ?",
            [$threadId, $messageId, $userId]
        );

        if (empty($messages)) {
            throw new Exception("Message (ID: {$messageId}) not found in thread (ID: {$threadId})");
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

        if (trim($reaction) === "") {
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
    public function deleteMessageReaction(
        int $threadId,
        int $messageId,
        $userId = User::LOGGED_IN_USER
    ): void {
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

        // Remove all reactions for the message from this user
        foreach ($reactions as $reaction) {
            $reaction->remove();
        }
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
        $messageThreadSummary = $this->getMessageThreadSummaryById($messageThreadId);
        $messageThreadSummary->remove();
    }
}
