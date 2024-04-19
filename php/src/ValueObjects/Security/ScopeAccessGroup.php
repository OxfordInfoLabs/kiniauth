<?php

namespace Kiniauth\ValueObjects\Security;

class ScopeAccessGroup {

    public function __construct(private array $scopeAccesses, private ?bool $writeAccess, private ?bool $grantAccess, private ?  ){

    }

}