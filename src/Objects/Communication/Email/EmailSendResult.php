<?php


namespace Kiniauth\Objects\Communication\Email;


use Kinikit\Core\Object\SerialisableObject;

class EmailSendResult extends SerialisableObject {

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
