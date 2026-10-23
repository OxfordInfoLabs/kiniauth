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
     * Message ID
     *
     * Used to link the same message across multiple users
     *
     * @var int
     */
    protected $messageId;

    /**
     * MessageThread ID
     *
     * @var int
     * @required
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
     *
     * @var string
     */
    protected $encryptedMessage;

    /**
     * Message Type
     *
     * @var string
     */
    protected $messageType;

    /**
     * Message Reactions array
     *
     * @unmapped
     * @var array
     */
    protected $messageReactions = [];

    /**
     * Sender User ID
     *
     * @var int
     */
    protected $senderUserId;

    /**
     * Sender Username
     *
     * @var string
     */
    protected $senderUserName;

    /**
     * Sender User Account ID
     *
     * @var int
     */
    protected $senderAccountId;

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
     * @param ?int      $messageThreadId
     * @param ?string   $encryptedMessage
     * @param ?string   $messageType
     * @param array     $messageReactions
     * @param ?int      $senderUserId
     * @param ?string   $senderUserName
     * @param ?int      $senderAccountId
     * @param ?int      $receiverUserId
     * @param ?int      $receiverAccountId
     * @param ?int      $receiverGroupId
     * @param ?DateTime $messageDate
     * @param ?int      $messageId
     */
    public function __construct(
        $messageThreadId,
        $encryptedMessage,
        $messageType = null,
        $messageReactions = [],
        $senderUserId = null,
        $senderUserName = null,
        $senderAccountId = null,
        $receiverUserId = null,
        $receiverAccountId = null,
        $receiverGroupId = null,
        $messageDate = null,
        $messageId = null
    ) {
        $this->messageThreadId = $messageThreadId;
        $this->encryptedMessage = $encryptedMessage;
        $this->messageType = $messageType;
        $this->messageReactions = $messageReactions;
        $this->senderUserId = $senderUserId;
        $this->senderUserName = $senderUserName;
        $this->senderAccountId = $senderAccountId;
        $this->receiverUserId = $receiverUserId;
        $this->receiverAccountId = $receiverAccountId;
        $this->receiverGroupId = $receiverGroupId;
        $this->messageDate = $messageDate;
        $this->messageId = $messageId;
    }


    public function getMessageId(): ?int {
        return $this->messageId;
    }

    public function setMessageId(?int $messageId): void {
        $this->messageId = $messageId;
    }

    public function getMessageThreadId(): int {
        return $this->messageThreadId;
    }

    public function setMessageThreadId(?int $messageThreadId): void {
        $this->messageThreadId = $messageThreadId;
    }

    public function getMessageDate(): DateTime|string|null {
        return $this->messageDate;
    }

    public function setMessageDate(DateTime|string|null $messageDate): void {
        $this->messageDate = $messageDate;
    }

    public function getEncryptedMessage(): ?string {
        return $this->encryptedMessage;
    }

    public function setEncryptedMessage(?string $encryptedMessage): void {
        $this->encryptedMessage = $encryptedMessage;
    }

    public function getMessageType(): ?string {
        return $this->messageType;
    }

    public function setMessageType(?string $messageType): void {
        $this->messageType = $messageType;
    }

    public function getMessageReactions(): ?array {
        return $this->messageReactions;
    }

    public function setMessageReactions(?array $messageReactions): void {
        $this->messageReactions = $messageReactions;
    }

    public function getSenderUserId(): ?int {
        return $this->senderUserId;
    }

    public function setSenderUserId(?int $senderUserId): void {
        $this->senderUserId = $senderUserId;
    }

    public function getSenderAccountId(): ?int {
        return $this->senderAccountId;
    }

    public function setSenderAccountId(?int $senderAccountId): void {
        $this->senderAccountId = $senderAccountId;
    }

    public function getSenderUserName(): ?string {
        return $this->senderUserName;
    }

    public function setSenderUserName(?string $senderUserName): void {
        $this->senderUserName = $senderUserName;
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


