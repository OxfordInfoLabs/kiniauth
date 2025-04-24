<?php

namespace Kiniauth\Objects\Account;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_account_security_domain
 * @generate
 */
class AccountSecurityDomain extends ActiveRecord {

    /**
     * @var integer
     */
    private $id;

    /**
     * @param string $domainName
     */
    public function __construct(private ?string $domainName) {
    }

    /**
     * @return string
     */
    public function getDomainName(): string {
        return $this->domainName;
    }

    /**
     * @param string $domainName
     */
    public function setDomainName(string $domainName): void {
        $this->domainName = $domainName;
    }


}