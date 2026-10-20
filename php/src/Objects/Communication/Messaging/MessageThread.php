<?php

namespace Kiniauth\Objects\Communication\Messaging;


/**
 *
 * @table ka_message_thread
 * @generate
 */
class MessageThread extends MessageThreadSummary{

    /**
     * MessageThread constructor
     *
     * @param MessageThreadSummary $messageThreadSummary
     */
    public function __construct($messageThreadSummary) {

        if ($messageThreadSummary) {
            parent::__construct(
                messageThreadUserId1: $messageThreadSummary->getMessageThreadUserId1(),
                messageThreadUserId2: $messageThreadSummary->getMessageThreadUserId2(),
                messageThreadAccountId: $messageThreadSummary->getMessageThreadAccountId(),
                messageThreadGroupId: $messageThreadSummary->getMessageThreadGroupId(),
            );
        }
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
            id: $this->id
        );
    }

}
