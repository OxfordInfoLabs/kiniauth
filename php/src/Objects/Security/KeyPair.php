<?php

namespace Kiniauth\Objects\Security;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Traits\Account\AccountProject;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_key_pair
 * @generate
 */
class KeyPair extends KeyPairSummary {

    use AccountProject;

    public function __construct(?KeyPairSummary $keyPairSummary, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        parent::__construct($keyPairSummary?->getDescription(), $keyPairSummary?->getPrivateKey(), $keyPairSummary?->getPublicKey(), $keyPairSummary?->getId());
        $this->accountId = $accountId;
        $this->projectKey = $projectKey;
    }

    /**
     * Generate a summary for a keypair
     *
     * @return KeyPairSummary
     */
    public function toSummary() {
        return new KeyPairSummary($this->getDescription(), $this->getPrivateKey(), $this->getPublicKey(), $this->getId());
    }

}