<?php

namespace Kiniauth\Controllers\Communication\Messaging;



use Kiniauth\Objects\Communication\Messaging\Message;
use Kiniauth\Objects\Communication\Messaging\MessageSummary;
use Kiniauth\Objects\Communication\Messaging\MessageThreadSummary;
use Kiniauth\Services\Communication\Messaging\MessagingService;

class Messaging {


    public function __construct(
        private readonly MessagingService $messagingService
    ) {
    }

    /**
     * @http POST /message
     *
     * @param MessageSummary $messageSummary
     *
     * @return int
     */
    public function newMessage($messageSummary): int {
        return $this->messagingService->saveMessage($messageSummary);
    }

    /**
     * @http GET /messageLatest
     *
     * @param int $threadId
     * @param int $userId
     *
     * @return MessageSummary
     */
    public function getLatestMessageByThread($threadId, $userId): MessageSummary {
        return $this->messagingService->getLatestMessageFromThread($threadId, $userId);
    }

    /**
     * @http GET /messageAll
     *
     * @param int $threadId
     * @param int $userId
     *
     * @return array
     */
    public function getAllMessagesByThread($threadId, $userId): array {
        return $this->messagingService->getAllMessagesFromThread($threadId, $userId);
    }

    /**
     * @http DELETE /message
     *
     * @param int $messageId
     * `
     * @void
     */
    public function deleteMessage($messageId): void {
        $this->messagingService->deleteMesssage($messageId);
    }

    /**
     * @http POST /thread
     *
     * @param MessageThreadSummary $threadSummary
     *
     * @return int
     */
    public function newMessageThread($threadSummary): int {
        return $this->messagingService->saveMessageThread($threadSummary);
    }

    /**
     * @http GET /thread/user
     *
     * @param int $messageThreadUserId
     *
     * @return array
     */
    public function getAllMessageThreadsByUserId($messageThreadUserId): array {
        return $this->getAllMessageThreadsByUserId($messageThreadUserId);
    }

    /**
     * @http GET /thread/account
     *
     * @param int $messageThreadAccountId
     *
     * @return array
     */
    public function getAllMessageThreadsByAccountId($messageThreadAccountId): array {
        return $this->getAllMessageThreadsByAccountId($messageThreadAccountId);
    }

    /**
     * @http GET /thread/group
     *
     * @param int $messageThreadGroupId
     *
     * @return array
     */
    public function getAllMessageThreadsByGroupId($messageThreadGroupId): array {
        return $this->getAllMessageThreadsByGroupId($messageThreadGroupId);
    }

    /**
     * @http DELETE /thread
     *
     * @param int $messageThreadId
     *
     * @return void
     */
    public function deleteMessageThread($messageThreadId): void {
        $this->messagingService->deleteMessageThread($messageThreadId);
    }

}