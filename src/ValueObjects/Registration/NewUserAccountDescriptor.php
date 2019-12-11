<?php

namespace Kiniauth\ValueObjects\Registration;

/**
 * Value object for use as a payload when creating new accounts
 *
 * Class NewUserAccountDescriptor
 */
class NewUserAccountDescriptor {

    /**
     * @var string
     */
    private $emailAddress;


    /**
     * @var string
     */
    private $name;


    /**
     * @var string
     */
    private $accountName;


    /**
     * @var string
     */
    private $password;

    /**
     * @return string
     */
    public function getEmailAddress() {
        return $this->emailAddress;
    }

    /**
     * @param string $emailAddress
     */
    public function setEmailAddress($emailAddress) {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }


    /**
     * @return string
     */
    public function getAccountName() {
        return $this->accountName;
    }

    /**
     * @param string $accountName
     */
    public function setAccountName($accountName) {
        $this->accountName = $accountName;
    }

    /**
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password) {
        $this->password = $password;
    }


}
