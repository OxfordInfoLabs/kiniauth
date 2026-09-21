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

    /**
     * Messages constructor
     *
     * @param MessageSummary $messageSummary
     */
    public function __construct($messageSummary) {

        if ($messageSummary) {
            parent::__construct(
                $messageSummary->getMessageThreadId(),
                $messageSummary->getEncryptedMessage(),
                $messageSummary->getMessageType(),
                $messageSummary->getSenderUserId(),
                $messageSummary->getReceiverUserId(),
                $messageSummary->getReceiverAccountId(),
                $messageSummary->getReceiverGroupId(),
                new DateTime(),
            );
        }
    }

    /**
     * Return a summary of this message
     *
     * @return MessageSummary
     */
    public function returnSummary(): MessageSummary {
        return new MessageSummary(
            $this->messageThreadId,
            $this->encryptedMessage,
            $this->messageType,
            $this->senderUserId,
            $this->receiverUserId,
            $this->receiverAccountId,
            $this->receiverGroupId,
            $this->messageDate,
            $this->id
        );
    }

}
