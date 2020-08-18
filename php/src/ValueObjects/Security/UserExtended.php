<?php

namespace Kiniauth\ValueObjects\Security;

use Kiniauth\Objects\Security\User;

class UserExtended {

    protected $id;
    protected $backupEmailAddress;
    protected $emailAddress;
    protected $mobileNumber;
    protected $fullEmailAddress;
    protected $name;
    protected $status;
    protected $roles;
    protected $twoFactorData;

    /**
     * UserExtended constructor.
     * @param User $user
     */
    public function __construct($user) {
        $this->id = $user->getId();
        $this->backupEmailAddress = $user->getBackupEmailAddress();
        $this->emailAddress = $user->getEmailAddress();
        $this->mobileNumber = $user->getMobileNumber();
        $this->fullEmailAddress = $user->getFullEmailAddress();
        $this->name = $user->getName();
        $this->status = $user->getStatus();
        $this->roles = $user->getRoles();
        $this->twoFactorData = $user->getTwoFactorData();
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getBackupEmailAddress() {
        return $this->backupEmailAddress;
    }

    /**
     * @return string|null
     */
    public function getEmailAddress() {
        return $this->emailAddress;
    }

    /**
     * @return string
     */
    public function getMobileNumber() {
        return $this->mobileNumber;
    }

    /**
     * @return string|null
     */
    public function getFullEmailAddress() {
        return $this->fullEmailAddress;
    }

    /**
     * @return string|null
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @return \Kiniauth\Objects\Security\UserRole[]
     */
    public function getRoles() {
        return $this->roles;
    }

    /**
     * @return string
     */
    public function getTwoFactorData() {
        return $this->twoFactorData;
    }


}
