<?php

namespace Kiniauth\Objects\Communication\Email;


use Kiniauth\Objects\Attachment\AttachmentSummary;
use Kinikit\Core\Communication\Email\Email;
use Kinikit\Persistence\UPF\Object\ActiveRecord;

/**
 *
 * @table ka_email
 * @generate
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
    public function __construct($email = null, $accountId = null, $userId = null, $status = null, $errorMessage = null) {

        if ($email) {
            $this->sender = $email->getFrom();
            $this->recipients = $email->getRecipients();
            $this->subject = $email->getSubject();
            $this->textBody = $email->getTextBody();
            $this->cc = $email->getCc();
            $this->bcc = $email->getBcc();
            $this->replyTo = $email->getReplyTo();
            $this->accountId = $accountId;
            $this->userId = $userId;
            $this->sentDate = new \DateTime();
            $this->status = $status;
            $this->errorMessage = $errorMessage;
        }

    }

    /**
     * @return string
     */
    public function getTextBody() {
        return $this->textBody;
    }

    /**
     * @return AttachmentSummary[]
     */
    public function getAttachments() {
        return $this->attachments;
    }


}
