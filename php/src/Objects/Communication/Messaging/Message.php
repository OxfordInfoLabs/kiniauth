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
     * Unique primary key
     *
     * @var int
     * @primaryKey
     * @autoIncrement
     */
    protected $id;

    /**
     * Messages constructor
     *
     * @param MessageSummary $messageSummary
     * @param ?int $id
     *
     * @throws Exception
     */
    public function __construct($messageSummary, $id = null) {

        if ($messageSummary) {
            parent::__construct(
                messageThreadId: $messageSummary->getMessageThreadId(),
                encryptedMessage: $messageSummary->getEncryptedMessage(),
                messageType: $messageSummary->getMessageType(),
                messageReactions: $messageSummary->getMessageReactions(),
                senderUserId: $messageSummary->getSenderUserId(),
                senderUserName: $messageSummary->getSenderUserName(),
                senderAccountId: $messageSummary->getSenderAccountId(),
                receiverUserId: $messageSummary->getReceiverUserId(),
                receiverAccountId: $messageSummary->getReceiverAccountId(),
                receiverGroupId: $messageSummary->getReceiverGroupId(),
                messageDate: new DateTime($messageSummary->getMessageDate()),
                messageId: $messageSummary->getMessageId(),
            );
        }

        $this->id = $id;
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
            messageReactions: $this->messageReactions,
            senderUserId: $this->senderUserId,
            senderUserName: $this->senderUserName,
            senderAccountId: $this->senderAccountId,
            receiverUserId: $this->receiverUserId,
            receiverAccountId: $this->receiverAccountId,
            receiverGroupId: $this->receiverGroupId,
            messageDate: $this->messageDate,
            messageId: $this->messageId
        );
    }

    public function getId(): ?int {
        return $this->id;
    }

}
