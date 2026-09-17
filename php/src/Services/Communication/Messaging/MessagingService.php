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

        $whereClauses = [];
        $params = [];

        if ($threadId) {
            $whereClauses[] = "messageThreadId = ?";
            $params[] = $threadId;
        }

        $query = (sizeof($whereClauses) ? "WHERE " : "") . join(" AND ", $whereClauses) . " ORDER BY id";

        $results = Message::filter($query, $params);
        return array_map(function ($item) {
            return $item->returnSummary();
        }, $results);
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
     * @param $id
     *
     * @return MessageThreadSummary
     */
    public function getMessageThreadSummaryById($id) {
        return MessageThread::fetch($id)->returnSummary();
    }

    /**
     * @param MessageThreadSummary $messageThreadSummary
     *
     * @return int
     */
    public function saveMessageThread($messageThreadSummary) {

        $messageThread = new MessageThread($messageThreadSummary);
        $messageThread->save();

        return $messageThread->getId();
    }

    /**
     * @param int $messageThreadId
     *
     * @return void
     */
    public function deleteMessageThread($messageThreadId) {
        $messageThreadSummary = $this->getMessageThreadSummaryById($messageThreadId);
        $messageThreadSummary->remove();
    }
}
