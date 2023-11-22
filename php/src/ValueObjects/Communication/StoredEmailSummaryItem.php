<?php

namespace Kiniauth\ValueObjects\Communication;

use Kiniauth\Objects\Communication\Email\StoredEmailSummary;

class StoredEmailSummaryItem {


    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $sentDate;


    /**
     * @var string
     */
    private $sender;


    /**
     * @var string[]
     */
    private $recipients;

    /**
     * @var string[]
     */
    private $cc;


    /**
     * @var string[]
     */
    private $bcc;


    /**
     * @var string
     */
    private $subject;


    /**
     * @var string
     */
    private $replyTo;


    /**
     * @var string
     */
    private $status;


    /**
     * @var string
     */
    private $errorMessage;

    /**
     * @param int $id
     * @param string $sentDate
     * @param string $sender
     * @param string[] $recipients
     * @param string[] $cc
     * @param string[] $bcc
     * @param string $subject
     * @param string $replyTo
     * @param string $status
     * @param string $errorMessage
     */
    public function __construct($id, $sentDate, $sender, $recipients, $cc, $bcc, $subject, $replyTo, $status, $errorMessage) {
        $this->id = $id;
        $this->sentDate = $sentDate;
        $this->sender = $sender;
        $this->recipients = $recipients;
        $this->cc = $cc;
        $this->bcc = $bcc;
        $this->subject = $subject;
        $this->replyTo = $replyTo;
        $this->status = $status;
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSentDate() {
        return $this->sentDate;
    }

    /**
     * @return string
     */
    public function getSender() {
        return $this->sender;
    }

    /**
     * @return string[]
     */
    public function getRecipients() {
        return $this->recipients;
    }

    /**
     * @return string[]
     */
    public function getCc() {
        return $this->cc;
    }

    /**
     * @return string[]
     */
    public function getBcc() {
        return $this->bcc;
    }

    /**
     * @return string
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getReplyTo() {
        return $this->replyTo;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }


    /**
     * Construct a summary item from a stored email summary
     *
     * @param StoredEmailSummary $storedEmailSummary
     * @return StoredEmailSummaryItem
     */
    public static function fromStoredEmailSummary($storedEmailSummary) {
        return new StoredEmailSummaryItem($storedEmailSummary->getId(),
            $storedEmailSummary->getSentDate() ? $storedEmailSummary->getSentDate()->format("Y-m-d H:i:s") : null,
            $storedEmailSummary->getSender(),
            $storedEmailSummary->getRecipients(),
            $storedEmailSummary->getCc(),
            $storedEmailSummary->getBcc(),
            $storedEmailSummary->getSubject(),
            $storedEmailSummary->getReplyTo(),
            $storedEmailSummary->getStatus(),
            $storedEmailSummary->getErrorMessage()
        );
    }


}