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
    public function newMessage($messageSummary) {
        return $this->messagingService->saveMessage($messageSummary);
    }

    /**
     * @http GET /messageAll
     *
     * @param int $threadId
     *
     * @return array
     */
    public function getAllMessagesByThread($threadId): array {
        return $this->messagingService->getAllMessagesFromThread($threadId);
    }

    /**
     * @http DELETE /message
     *
     * @param int $messageId
     * `
     * @void
     */
    public function deleteMessage($messageId) {
        $this->messagingService->deleteMesssage($messageId);
    }

    /**
     * @http POST /thread
     *
     * @param MessageThreadSummary $threadSummary
     *
     * @return int
     */
    public function newMessageThread($threadSummary) {
        return $this->messagingService->saveMessageThread($threadSummary);
    }

    /**
     * @http DELETE /thread
     *
     * @param int $messageThreadId
     *
     * @return void
     */
    public function deleteMessageThread($messageThreadId) {
        $this->messagingService->deleteMessageThread($messageThreadId);
    }

}