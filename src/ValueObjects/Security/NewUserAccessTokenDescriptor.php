<?php


namespace Kiniauth\ValueObjects\Security;


class NewUserAccessTokenDescriptor {

    /**
     * @var string
     */
    private $emailAddress;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $twoFactorCode;

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
    public function getPassword() {
        return $this->password;
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
    public function getTwoFactorCode() {
        return $this->twoFactorCode;
    }

    /**
     * @param string $twoFactorCode
     */
    public function setTwoFactorCode($twoFactorCode) {
        $this->twoFactorCode = $twoFactorCode;
    }


}
