<?php


namespace Kiniauth\Services\Communication\Messaging;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Messaging\Message;
use Kiniauth\Objects\Communication\Messaging\MessageSummary;
use Kiniauth\Objects\Communication\Messaging\MessageThread;


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
     * @param string $projectKey
     * @param int $accountId
     *
     * @return int
     */
    public function saveMessage($messageSummary, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT): int {

        $message = new Message($messageSummary, $projectKey, $accountId);
        $message->save();

        return $message->getId();
    }

    /**
     * Get all messages from a thread
     *
     * @param int $threadId
     * @param string $projectKey
     * @param int $accountId
     *
     * @return array
     */
    public function getAllMessagesFromThread($threadId, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT): array {

        $whereClauses = [];
        $params = [];

        if ($threadId) {
            $whereClauses[] = "message_thread_id = ?";
            $params[] = $threadId;
        }

        if ($accountId) {
            $whereClauses[] = "accountId = ?";
            $params[] = $accountId;
        }

        if ($projectKey) {
            $whereClauses[] = "projectKey = ?";
            $params[] = $projectKey;
        }

        $query = (sizeof($whereClauses) ? "WHERE " : "") . join(" AND ", $whereClauses) . " ORDER BY id";

        $results = Message::filter($query, $params);
        return array_map(function ($item) {
            return $item->returnSummary();
        }, $results);
    }

}
