<?php


namespace Kiniauth\Objects\Communication\Email;


use Kinikit\Persistence\UPF\Object\ActiveRecord;


/**
 * Summary class for listing of emails
 *
 * @noGeneratedTable
 */
class EmailSummary extends ActiveRecord {


    /**
     * Numeric id for this email
     *
     * @var integer
     */
    protected $id;

    /**
     * The account id for which this email refers if applicable.
     *
     * @var integer
     */
    protected $accountId;


    /**
     * Sent date for this email
     *
     * @var string
     * @validation required
     * @formatter britishdatetime
     * @ormType DATETIME
     */
    protected $sentDate;


    /**
     * From field
     *
     * @var string
     * @validation required
     *
     */
    protected $sender;


    /**
     * To field
     *
     * @var string[]
     * @validation required
     * @ormType TEXT
     * @formatter json
     */
    protected $recipients;

    /**
     * Optional CC field
     *
     * @var string[]
     * @ormType TEXT
     * @formatter json
     */
    protected $cc;

    /**
     * Optional BCC field
     *
     * @var string[]
     * @ormType TEXT
     * @formatter json
     */
    protected $bcc;


    /**
     * Subject field
     *
     * @var string
     * @validation required
     */
    protected $subject;

    /**
     * Optional reply to
     *
     * @var string
     */
    protected $replyTo;

    /**
     * An error string if an error occurred sending this email
     *
     * @var string
     * @ormType TEXT
     */
    protected $errorMessage;


    /**
     * The sent status of this email
     *
     * @var string
     * @validation maxlength(30)
     */
    protected $status;

    const STATUS_SENT = "SENT";
    const STATUS_FAILED = "FAILED";

    /**
     * @return string
     */
    public function getSentDate() {
        return $this->sentDate;
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
    public function getErrorMessage() {
        return $this->errorMessage;
    }

    /**
     * @return string
     */
    public function getSender() {
        return $this->sender;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getAccountId() {
        return $this->accountId;
    }

    /**
     * @return string[]
     */
    public function getRecipients() {
        return $this->recipients;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }


}


