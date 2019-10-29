<?php

namespace Kiniauth\ValueObjects\Security;

/**
 * Payload object for resetting passwords
 *
 * Class NewPasswordDescriptor
 */
class NewPasswordDescriptor {

    /**
     * New password
     *
     * @var string
     */
    private $newPassword;

    /**
     * 16 digit reset code
     *
     * @var string
     */
    private $resetCode;

    /**
     * @return string
     */
    public function getNewPassword() {
        return $this->newPassword;
    }

    /**
     * @param string $newPassword
     */
    public function setNewPassword($newPassword) {
        $this->newPassword = $newPassword;
    }

    /**
     * @return string
     */
    public function getResetCode() {
        return $this->resetCode;
    }

    /**
     * @param string $resetCode
     */
    public function setResetCode($resetCode) {
        $this->resetCode = $resetCode;
    }


}
