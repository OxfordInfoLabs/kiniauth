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
     * Message thread user id (first user)
     *
     * @var int
     */
    protected $messageThreadUserId1;

    /**
     * Message thread user id (second user)
     *
     * @var int
     */
    protected $messageThreadUserId2;

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
     * Thread message count
     *
     * @var int
     */
    protected $messageCount;

    /**
     * MessageSummary constructor.
     *
     * @param int       $messageThreadUserId1
     * @param int       $messageThreadUserId2
     * @param int       $messageThreadAccountId
     * @param int       $messageThreadGroupId
     * @param int       $messageCount
     * @param int       $messageThreadId
     */
    public function __construct(
        $messageThreadUserId1 = null,
        $messageThreadUserId2 = null,
        $messageThreadAccountId = null,
        $messageThreadGroupId = null,
        $messageCount = 0,
        $messageThreadId = null,
    ) {
        $this->messageThreadUserId1 = $messageThreadUserId1;
        $this->messageThreadUserId2 = $messageThreadUserId2;
        $this->messageThreadAccountId = $messageThreadAccountId;
        $this->messageThreadGroupId = $messageThreadGroupId;
        $this->messageCount = $messageCount;
        $this->id = $messageThreadId;
    }


    public function getMessageThreadId(): ?int {
        return $this->id;
    }

    public function getMessageThreadUserId1(): ?int {
        return $this->messageThreadUserId1;
    }

    public function getMessageThreadUserId2(): ?int {
        return $this->messageThreadUserId2;
    }

    public function setMessageThreadUserId1(?int $messageThreadUserId1): void {
        $this->messageThreadUserId1 = $messageThreadUserId1;
    }

    public function setMessageThreadUserId2(?int $messageThreadUserId2): void {
        $this->messageThreadUserId2 = $messageThreadUserId2;
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

    public function getMessageCount(): ?int {
        return $this->messageCount;
    }

    public function setMessageCount(?int $messageCount): void {
        $this->messageCount = $messageCount;
    }

}


