<?php

namespace Kiniauth\Objects\Communication\Messaging;


use DateTime;
use Exception;

/**
 *
 * @table ka_message_reaction
 * @generate
 */
class MessageReaction extends MessageReactionSummary {

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
     * @param MessageReactionSummary $messageReactionSummary
     * @param ?int $id
     *
     * @throws Exception
     */
    public function __construct($messageReactionSummary, $id = null) {

        if ($messageReactionSummary) {
            parent::__construct(
                messageId: $messageReactionSummary->getMessageId(),
                messageThreadId: $messageReactionSummary->getMessageThreadId(),
                reaction: $messageReactionSummary->getReaction(),
                senderUserId: $messageReactionSummary->getSenderUserId(),
                senderUserName: $messageReactionSummary->getSenderUserName(),
                reactionDate: new DateTime($messageReactionSummary->getReactionDate()),
            );
        }

        $this->id = $id;
    }

    /**
     * Return a summary of this message
     *
     * @return MessageReactionSummary
     */
    public function returnSummary(): MessageReactionSummary {
        return new MessageReactionSummary(
            messageId: $this->messageId,
            messageThreadId :$this->messageThreadId,
            reaction: $this->reaction,
            senderUserId: $this->senderUserId,
            senderUserName: $this->senderUserName,
            reactionDate: $this->reactionDate,
        );
    }

    public function getId(): ?int {
        return $this->id;
    }

}
