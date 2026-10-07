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
                $messageThreadSummary->getMessageThreadUserId1(),
                $messageThreadSummary->getMessageThreadUserId2(),
                $messageThreadSummary->getMessageThreadAccountId(),
                $messageThreadSummary->getMessageThreadGroupId(),
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
            $this->messageThreadUserId1,
            $this->messageThreadUserId2,
            $this->messageThreadAccountId,
            $this->messageThreadGroupId,
            $this->messageCount,
            $this->id
        );
    }

}
