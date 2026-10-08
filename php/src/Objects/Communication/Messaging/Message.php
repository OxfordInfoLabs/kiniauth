<?php

namespace Kiniauth\Objects\Communication\Messaging;


use DateTime;
use Exception;
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
     * @throws Exception
     */
    public function __construct($messageSummary) {

        if ($messageSummary) {
            parent::__construct(
                messageThreadId: $messageSummary->getMessageThreadId(),
                encryptedMessage: $messageSummary->getEncryptedMessage(),
                messageType: $messageSummary->getMessageType(),
                senderUserId: $messageSummary->getSenderUserId(),
                senderAccountId: $messageSummary->getSenderAccountId(),
                receiverUserId: $messageSummary->getReceiverUserId(),
                receiverAccountId: $messageSummary->getReceiverAccountId(),
                receiverGroupId: $messageSummary->getReceiverGroupId(),
                messageDate: new DateTime($messageSummary->getMessageDate()),
                messageId: $messageSummary->getMessageId()
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
            messageThreadId :$this->messageThreadId,
            encryptedMessage: $this->encryptedMessage,
            messageType: $this->messageType,
            senderUserId: $this->senderUserId,
            senderAccountId: $this->senderAccountId,
            receiverUserId: $this->receiverUserId,
            receiverAccountId: $this->receiverAccountId,
            receiverGroupId: $this->receiverGroupId,
            messageDate: $this->messageDate,
            messageId: $this->messageId
        );
    }

}
