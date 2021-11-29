<?php

namespace Kiniauth\ValueObjects\Registration;

/**
 * Value object for use as a payload when creating new accounts
 *
 * Class NewUserAccountDescriptor
 */
class NewUserAccountDescriptor extends NewUserDescriptor {


    /**
     * @var string
     */
    private $accountName;


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


}
