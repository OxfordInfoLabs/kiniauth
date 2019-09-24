<?php


namespace Kiniauth\Objects\Communication\Email;


/**
 * @noGenerate
 *
 * Class EmailSendResult
 * @package Kiniauth\Objects\Communication\Email
 */
class EmailSendResult  {

    private $status;
    private $emailId;
    private $errorMessage;

    /**
     * Email send result
     *
     * @param $status
     * @param $errorMessage
     */
    public function __construct($status = null, $errorMessage = null, $emailId = null) {
        $this->status = $status;
        $this->errorMessage = $errorMessage;
        $this->emailId = $emailId;
    }


    /**
     * @return mixed
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getEmailId() {
        return $this->emailId;
    }


    /**
     * @return mixed
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }


}
