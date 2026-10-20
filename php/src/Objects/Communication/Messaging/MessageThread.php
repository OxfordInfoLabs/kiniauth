<?php

namespace Kiniauth\Objects\Communication\Messaging;


/**
 *
 * @table ka_message_thread
 * @generate
 */
class MessageThread extends MessageThreadSummary{

    /**
     * Unique primary key
     *
     * @var int
     * @primaryKey
     * @autoIncrement
     */
    protected $id;

    /**
     * MessageThread constructor
     *
     * @param MessageThreadSummary $messageThreadSummary
     * @param ?int $id
     */
    public function __construct($messageThreadSummary, $id = null) {

        if ($messageThreadSummary) {
            parent::__construct(
                messageThreadUserId1: $messageThreadSummary->getMessageThreadUserId1(),
                messageThreadUserId2: $messageThreadSummary->getMessageThreadUserId2(),
                messageThreadAccountId: $messageThreadSummary->getMessageThreadAccountId(),
                messageThreadGroupId: $messageThreadSummary->getMessageThreadGroupId(),
            );
        }
        $this->id = $id;
    }

    /**
     * Return a summary of this message thread
     *
     * @return MessageThreadSummary
     */
    public function returnSummary(): MessageThreadSummary {
        return new MessageThreadSummary(
            messageThreadUserId1: $this->messageThreadUserId1,
            messageThreadUserId2: $this->messageThreadUserId2,
            messageThreadAccountId: $this->messageThreadAccountId,
            messageThreadGroupId: $this->messageThreadGroupId,
            messageCount: $this->messageCount,
        );
    }

    public function getId(): ?int {
        return $this->id;
    }

}
