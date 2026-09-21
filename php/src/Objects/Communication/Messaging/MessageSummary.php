<?php


namespace Kiniauth\Objects\Communication\Messaging;

use DateTime;
use Kiniauth\ValueObjects\Communication\Messaging\MessageType;
use Kinikit\Persistence\ORM\ActiveRecord;


/**
 * Summary class for listing of messages
 *
 * @table ka_message
 */
class MessageSummary extends ActiveRecord {


    /**
     * Unique primary key
     *
     * @var int
     * @primaryKey
     * @autoIncrement
     */
    protected $id;

    /**
     * MessageThread ID
     *
     * @var int
     */
    protected $messageThreadId;

    /**
     * Date message send
     *
     * @var DateTime
     */
    protected $messageDate;

    /**
     * Message text [encrypted]
     * TODO: encrypt the text
     *
     * @var string
     */
    protected $encryptedMessage;

    /**
     * Message Type
     *
     * @var MessageType
     */
    protected $messageType;

    /**
     * Sender User ID
     *
     * @var int
     */
    protected $senderUserId;

    /**
     * Receiver User ID
     *
     * @var int
     */
    protected $receiverUserId;

    /**
     * Receiver Account ID
     *
     * @var int
     */
    protected $receiverAccountId;

    /**
     * Receiver Group ID
     *
     * @var int
     */
    protected $receiverGroupId;


    /**
     * MessageSummary constructor.
     *
     * @param int       $messageThreadId
     * @param string    $encryptedMessage
     * @param MessageType    $messageType
     * @param int       $senderUserId
     * @param int       $receiverUserId
     * @param int       $receiverAccountId
     * @param int       $receiverGroupId
     */
    public function __construct(
        $messageThreadId = null,
        $encryptedMessage = null,
        $messageType = null,
        $senderUserId = null,
        $receiverUserId = null,
        $receiverAccountId = null,
        $receiverGroupId = null,
        $messageDate = null,
        $id = null,
    ) {
        $this->messageThreadId = $messageThreadId;
        $this->encryptedMessage = $encryptedMessage;
        $this->messageType = $messageType;
        $this->senderUserId = $senderUserId;
        $this->receiverUserId = $receiverUserId;
        $this->receiverAccountId = $receiverAccountId;
        $this->receiverGroupId = $receiverGroupId;
        $this->messageDate = $messageDate;
        $this->id = $id;
    }


    public function getId(): ?int {
        return $this->id;
    }

    public function getMessageThreadId(): int {
        return $this->messageThreadId;
    }

    public function setMessageThreadId(?int $messageThreadId): void {
        $this->messageThreadId = $messageThreadId;
    }

    public function getMessageDate(): ?DateTime {
        return $this->messageDate;
    }

    public function setMessageDate(?DateTime $messageDate): void {
        $this->messageDate = $messageDate;
    }

    public function getEncryptedMessage(): ?string {
        return $this->encryptedMessage;
    }

    public function setEncryptedMessage(?string $encryptedMessage): void {
        $this->encryptedMessage = $encryptedMessage;
    }

    public function getMessageType(): ?MessageType {
        return $this->messageType;
    }

    public function setMessageType(?MessageType $messageType): void {
        $this->messageType = $messageType;
    }

    public function getSenderUserId(): ?int {
        return $this->senderUserId;
    }

    public function setSenderUserId(?int $senderUserId): void {
        $this->senderUserId = $senderUserId;
    }

    public function getReceiverUserId(): ?int {
        return $this->receiverUserId;
    }

    public function setReceiverUserId(?int $receiverUserId): void {
        $this->receiverUserId = $receiverUserId;
    }

    public function getReceiverAccountId(): ?int {
        return $this->receiverAccountId;
    }

    public function setReceiverAccountId(?int $receiverAccountId): void {
        $this->receiverAccountId = $receiverAccountId;
    }

    public function getReceiverGroupId(): ?int {
        return $this->receiverGroupId;
    }

    public function setReceiverGroupId(?int $receiverGroupId): void {
        $this->receiverGroupId = $receiverGroupId;
    }


}


