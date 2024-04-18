<?php

namespace Kiniauth\Test\Services\Security;

use Kiniauth\Objects\Account\Contact;
use Kiniauth\Traits\Security\Sharable;

class SharableContact extends Contact {

    use Sharable;

    public function __construct($name = null, $organisation = null, $street1 = null, $street2 = null, $city = null,
                                $county = null, $postcode = null, $countryCode = null, $telephoneNumber = null,
                                $emailAddress = null, $accountId = null, $type = self::ADDRESS_TYPE_GENERAL, $objectScopeAccesses = []) {

        parent::__construct($name, $organisation, $street1, $street2, $city, $county, $postcode, $countryCode, $telephoneNumber, $emailAddress, $accountId, $type);
        $this->objectScopeAccesses = $objectScopeAccesses;
    }
}