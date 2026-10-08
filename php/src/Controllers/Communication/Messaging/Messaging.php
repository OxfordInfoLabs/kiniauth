<?php

namespace Kiniauth\Controllers\Communication\Messaging;


use Exception;
use Kiniauth\Objects\Communication\Messaging\Message;
use Kiniauth\Objects\Communication\Messaging\MessageSummary;
use Kiniauth\Objects\Communication\Messaging\MessageThreadSummary;
use Kiniauth\Objects\Security\User;
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
     * @return void
     * @throws Exception
     */
    public function newMessage($messageSummary): void {
        $this->messagingService->saveMessage(
            messageSummary: $messageSummary
        );
    }

    /**
     * @http GET /messageLatest
     *
     * @param int $threadId
     * @param int $userId
     *
     * @return MessageSummary
     * @throws Exception
     */
    public function getLatestMessageByThread($threadId, $userId = User::LOGGED_IN_USER): MessageSummary {
        return $this->messagingService->getLatestMessageFromThread(
            threadId: $threadId,
            userId: $userId
        );
    }

    /**
     * @http GET /messageAll
     *
     * @param int $threadId
     * @param int $userId
     *
     * @return array
     * @throws Exception
     */
    public function getAllMessagesByThread($threadId, $userId = User::LOGGED_IN_USER): array {
        return $this->messagingService->getAllMessagesFromThread(
            threadId: $threadId,
            userId: $userId
        );
    }

    /**
     * @http DELETE /message
     *
     * @param int $threadId
     * @param int $messageId
     * @param int $userId
     * `
     * @void
     */
    public function deleteMessageForUser($threadId, $messageId, $userId = User::LOGGED_IN_USER): void {
        $this->messagingService->deleteMessageForUser(
            threadId: $threadId,
            messageId: $messageId,
            userId: $userId
        );
    }

    /**
     * @http DELETE /messageAll
     *
     * @param int $threadId
     * @param int $messageId
     *
     * @void
     */
    public function deleteMessageForAll($threadId, $messageId): void {
        $this->messagingService->deleteMessageForAllUsers(
            threadId: $threadId,
            messageId: $messageId
        );
    }

    /**
     * @http POST /thread
     *
     * @param MessageThreadSummary $threadSummary
     *
     * @return int
     */
    public function newMessageThread($threadSummary): int {
        return $this->messagingService->saveMessageThread(
            messageThreadSummary: $threadSummary
        );
    }

    /**
     * @http GET /thread/user
     *
     * @param int $messageThreadUserId
     *
     * @return array
     */
    public function getAllMessageThreadsByUserId($messageThreadUserId): array {
        return $this->messagingService->getAllMessageThreadsByUserId(
            messageThreadUserId: $messageThreadUserId
        );
    }

    /**
     * @http GET /thread/account
     *
     * @param int $messageThreadAccountId
     *
     * @return array
     */
    public function getAllMessageThreadsByAccountId($messageThreadAccountId): array {
        return $this->messagingService->getAllMessageThreadsByAccountId(
            messageThreadAccountId: $messageThreadAccountId
        );
    }

    /**
     * @http GET /thread/group
     *
     * @param int $messageThreadGroupId
     *
     * @return array
     */
    public function getAllMessageThreadsByGroupId($messageThreadGroupId): array {
        return $this->messagingService->getAllMessageThreadsByGroupId(
            messageThreadGroupId: $messageThreadGroupId
        );
    }

    /**
     * @http DELETE /thread
     *
     * @param int $messageThreadId
     *
     * @return void
     */
    public function deleteMessageThread($messageThreadId): void {
        $this->messagingService->deleteMessageThread(
            messageThreadId: $messageThreadId
        );
    }

}