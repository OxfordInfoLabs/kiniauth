<?php


namespace Kiniauth\ValueObjects\Registration;


class NewUserDescriptor {
    /**
     * @var string
     */
    protected $password;
    /**
     * @var string
     */
    protected $emailAddress;
    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $emailAddress
     */
    public function setEmailAddress($emailAddress) {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return string
     */
    public function getPassword() {
        return $this->password;
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
    public function getEmailAddress() {
        return $this->emailAddress;
    }

    /**
     * @param string $password
     */
    public function setPassword($password) {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }
}