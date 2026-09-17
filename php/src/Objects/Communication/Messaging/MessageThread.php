<?php

namespace Kiniauth\Objects\Communication\Messaging;


use Exception;

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
    public function __construct(MessageThreadSummary $messageThreadSummary) {
        if ($messageThreadSummary) {
            parent::__construct(
                $messageThreadSummary->getId()
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
            $this->id
        );
    }

}
