<?php


namespace Kiniauth\Objects\Communication\Messaging;

use DateTime;
use Kinikit\Persistence\ORM\ActiveRecord;


/**
 * Summary class for listing of messages
 *
 * @table ka_message_reaction
 */
class MessageReactionSummary extends ActiveRecord {


    /**
     * Message ID
     *
     * Used to link the reaction to the message
     *
     * @var int
     * @required
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
     * Date message reaction was made
     *
     * @var DateTime
     */
    protected $reactionDate;

    /**
     * Message Reaction
     *
     * @var string
     * @required
     */
    protected $reaction;

    /**
     * Reaction Sender User ID
     *
     * @var int
     * @required
     */
    protected $senderUserId;

    /**
     * Reaction Sender Username
     *
     * @var string
     */
    protected $senderUserName;


    /**
     * MessageSummary constructor.
     *
     * @param ?int      $messageId
     * @param ?int      $messageThreadId
     * @param ?string   $reaction
     * @param ?int      $senderUserId
     * @param ?string   $senderUserName
     * @param ?DateTime $reactionDate
     */
    public function __construct(
        $messageId,
        $messageThreadId,
        $reaction,
        $senderUserId,
        $senderUserName = null,
        $reactionDate = null,
    ) {
        $this->messageThreadId = $messageThreadId;
        $this->reaction = $reaction;
        $this->senderUserId = $senderUserId;
        $this->senderUserName = $senderUserName;
        $this->reactionDate = $reactionDate;
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

    public function getReactionDate(): mixed {
        return $this->reactionDate;
    }

    public function setReactionDate(mixed $reactionDate): void {
        $this->reactionDate = $reactionDate;
    }

    public function getReaction(): ?string {
        return $this->reaction;
    }

    public function setReaction(?string $reaction): void {
        $this->reaction = $reaction;
    }

    public function getSenderUserId(): ?int {
        return $this->senderUserId;
    }

    public function setSenderUserId(?int $senderUserId): void {
        $this->senderUserId = $senderUserId;
    }

    public function getSenderUserName(): ?string {
        return $this->senderUserName;
    }

    public function setSenderUserName(?string $senderUserName): void {
        $this->senderUserName = $senderUserName;
    }
}


