<?php


namespace Kiniauth\Services\Communication\Messaging;


use Exception;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Messaging\Message;
use Kiniauth\Objects\Communication\Messaging\MessageSummary;
use Kiniauth\Objects\Communication\Messaging\MessageThread;
use Kiniauth\Objects\Communication\Messaging\MessageThreadSummary;


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
     */
    public function saveMessage($messageSummary): int {

        $message = new Message($messageSummary);
        $message->save();

        return $message->getId();
    }

    /**
     * Get all messages from a thread
     *
     * @param int $threadId
     *
     * @return array
     */
    public function getAllMessagesFromThread($threadId): array {

        $query = "WHERE messageThreadId = ? ORDER BY id DESC";

        $results = Message::filter($query, [$threadId]);
        return array_map(function ($item) {
            return $item->returnSummary();
        }, $results);
    }

    /**
     * Get the latest message from a thead
     *
     * @param int $threadId
     *
     * @returns MessageSummary
     *
     */
    public function getLatestMessageFromThread($threadId): ?MessageSummary {

        $query = "WHERE messageThreadId = ? ORDER BY id DESC LIMIT 1";

        $results = Message::filter($query, [$threadId]);

        if (empty($results)) {
            return null;
        }

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
