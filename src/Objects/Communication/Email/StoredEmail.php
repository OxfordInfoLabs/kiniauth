<?php

namespace Kiniauth\Objects\Communication\Email;


use Kiniauth\Objects\Communication\Attachment\Attachment;
use Kiniauth\Objects\Communication\Attachment\AttachmentSummary;
use Kinikit\Core\Communication\Email\Email;
use Kinikit\Persistence\UPF\Object\ActiveRecord;

/**
 *
 * @table ka_email
 */
class StoredEmail extends StoredEmailSummary {


    /**
     * The main text body for this email.
     *
     * @var string
     * @required
     * @sqlType LONGTEXT
     */
    private $textBody;

    /**
     * Array of attachment summary objects summarising any attachments for this email
     *
     * @oneToMany
     * @readOnly
     * @childJoinColumns parent_object_id, parent_object_type=Email
     * @var AttachmentSummary[]
     */
    private $attachments;


    /**
     * Construct with a native Kinikit email.
     *
     * StoredEmail constructor.
     * @param Email $email
     * @throws \Exception
     */
    public function __construct($email = null, $accountId = null, $status = null, $errorMessage = null) {

        if ($email) {
            $this->sender = $email->getFrom();
            $this->recipients = $email->getRecipients();
            $this->subject = $email->getSubject();
            $this->textBody = $email->getTextBody();
            $this->cc = $email->getCc();
            $this->bcc = $email->getBcc();
            $this->replyTo = $email->getReplyTo();
            $this->accountId = $accountId;
            $this->sentDate = new \DateTime();
            $this->status = $status;
            $this->errorMessage = $errorMessage;
        }

    }


    /**
     * @param string $date
     */
    public function setSentDate($sentDate) {
        $this->sentDate = $sentDate;
    }

    /**
     * @param string[] $cc
     */
    public function setCc($cc) {
        $this->cc = $cc;
    }

    /**
     * @param string[] $bcc
     */
    public function setBcc($bcc) {
        $this->bcc = $bcc;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject) {
        $this->subject = $subject;
    }

    /**
     * @param string $replyTo
     */
    public function setReplyTo($replyTo) {
        $this->replyTo = $replyTo;
    }

    /**
     * @param string $errorMessage
     */
    public function setErrorMessage($errorMessage) {
        $this->errorMessage = $errorMessage;
    }

    /**
     * @param string $from
     */
    public function setSender($sender) {
        $this->sender = $sender;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @param string[] $recipients
     */
    public function setRecipients($recipients) {
        $this->recipients = $recipients;
    }

    /**
     * @param string $status
     */
    public function setStatus($status) {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getTextBody() {
        return $this->textBody;
    }

    /**
     * @param string $textBody
     */
    public function setTextBody($textBody) {
        $this->textBody = $textBody;
    }

    /**
     * @return AttachmentSummary[]
     */
    public function getAttachments() {
        return $this->attachments;
    }

    /**
     * @param AttachmentSummary[] $attachments
     */
    public function setAttachments($attachments) {
        $this->attachments = $attachments;
    }


}
