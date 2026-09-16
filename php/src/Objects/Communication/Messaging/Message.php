<?php

namespace Kiniauth\Objects\Communication\Messaging;


use DateTime;
use Kiniauth\Traits\Account\AccountProject;

/**
 *
 * @table ka_message
 * @generate
 */
class Message extends MessageSummary {
    use AccountProject;

    /**
     * Messages constructor
     *
     * @param MessageSummary $messageSummary
     * @param string $projectKey
     * @param int $accountId
     */
    public function __construct($messageSummary, $projectKey = null, $accountId = null) {

        if ($messageSummary) {
            parent::__construct(
                $messageSummary->getMessageThreadId(),
                $messageSummary->getMessageText(),
                $messageSummary->getSenderUserId(),
                $messageSummary->getReceiverUserId(),
                $messageSummary->getReceiverAccountId(),
                $messageSummary->getReceiverGroupId(),
                new DateTime(),
            );
        }

        $this->projectKey = $projectKey;
        $this->accountId = $accountId;

    }

    /**
     * Return a summary of this message
     *
     * @return MessageSummary
     */
    public function returnSummary(): MessageSummary {
        return new MessageSummary(
            $this->messageThreadId,
            $this->messageText,
            $this->senderUserId,
            $this->receiverUserId,
            $this->receiverAccountId,
            $this->receiverGroupId,
            $this->messageDate,
            $this->id
        );
    }

}
