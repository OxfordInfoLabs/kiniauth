<?php


namespace Kiniauth\Objects\Communication\Messaging;

use Kinikit\Persistence\ORM\ActiveRecord;


/**
 * Summary class for listing of messages
 *
 * @table ka_message_thread
 */
class MessageThreadSummary extends ActiveRecord {


    /**
     * Unique primary key
     *
     * @autoIncrement
     */
    protected $id;

    /**
     * Message thread id
     *
     * @var int
     */
    protected $messageThreadId;

    /**
     * Message thread user id
     *
     * @var int
     */
    protected $messageThreadUserId;

    /**
     * Message thread account id
     *
     * @var int
     */
    protected $messageThreadAccountId;

    /**
     * Message thread group id
     *
     * @var int
     */
    protected $messageThreadGroupId;

    /**
     * MessageSummary constructor.
     *
     * @param int       $messageThreadId
     * @param int       $messageThreadUserId
     * @param int       $messageThreadAccountId
     * @param int       $messageThreadGroupId
     * @param int       $id
     */
    public function __construct(
        $messageThreadId = null,
        $messageThreadUserId = null,
        $messageThreadAccountId = null,
        $messageThreadGroupId = null,
        $id = null,
    ) {
        $this->messageThreadId = $messageThreadId;
        $this->messageThreadUserId = $messageThreadUserId;
        $this->messageThreadAccountId = $messageThreadAccountId;
        $this->messageThreadGroupId = $messageThreadGroupId;
        $this->id = $id;
    }


    public function getId(): ?int {
        return $this->id;
    }

    public function getMessageThreadId(): ?int {
        return $this->messageThreadId;
    }

    public function setMessageThreadId(?int $messageThreadId) {
        $this->messageThreadId = $messageThreadId;
    }

    public function getMessageThreadUserId(): ?int {
        return $this->messageThreadUserId;
    }

    public function setMessageThreadUserId(?int $messageThreadUserId): void {
        $this->messageThreadUserId = $messageThreadUserId;
    }

    public function getMessageThreadAccountId(): ?int {
        return $this->messageThreadAccountId;
    }

    public function setMessageThreadAccountId(?int $messageThreadAccountId): void {
        $this->messageThreadAccountId = $messageThreadAccountId;
    }

    public function getMessageThreadGroupId(): ?int {
        return $this->messageThreadGroupId;
    }

    public function setMessageThreadGroupId(?int $messageThreadGroupId): void {
        $this->messageThreadGroupId = $messageThreadGroupId;
    }



}


