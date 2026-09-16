<?php

namespace Kiniauth\Controllers\Communication\Messaging;



use Kiniauth\Objects\Communication\Messaging\Message;
use Kiniauth\Services\Communication\Messaging\MessagingService;

class Messaging {



    public function __construct(
        private readonly MessagingService $messagingService
    ) {
    }

    /**
     * @http GET /$threadId
     *
     * @param int $threadId
     *
     * @return array
     */
    public function getAllMessagesByThread($threadId): array {
        return $this->messagingService->getAllMessagesFromThread($threadId);
    }

}