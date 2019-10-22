<?php


namespace Kiniauth\Objects\Communication\Email;

use Kinikit\Core\Communication\Email\EmailSendResult;

/**
 * Class EmailSendResult
 * @package Kiniauth\Objects\Communication\Email
 */
class StoredEmailSendResult extends EmailSendResult {

    private $emailId;

    /**
     * Email send result
     *
     * @param $status
     * @param $errorMessage
     */
    public function __construct($status = null, $errorMessage = null, $emailId = null) {
        parent::__construct($status, $errorMessage);
        $this->emailId = $emailId;
    }


    /**
     * @return mixed
     */
    public function getEmailId() {
        return $this->emailId;
    }


}
