<?php

namespace Kiniauth\Test\Services\Security;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\Contact;
use Kiniauth\Objects\Security\ObjectScopeAccess;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\Security\ScopeManager;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;

include_once "autoloader.php";

class ScopeManagerTest extends TestBase {

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    public function setUp(): void {
        $this->scopeManager = Container::instance()->get(ScopeManager::class);
    }

    public function testCanGenerateObjectScopesForObjectAndScope() {

        // Simple object - no object scopes
        $test = new TestNonAccountObject(23, "Hello", "Test");
        $this->assertEquals([], $this->scopeManager->generateObjectScopeAccesses($test, Role::SCOPE_ACCOUNT));

        // Simple non-sharable object with scope identified field
        $contact = new Contact("Me", "Test", "1 The Lane", null, null, null, "OX3 2WR", "GB", null, null, 2);
        $this->assertEquals([new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 2, "OWNER", true, true)], $this->scopeManager->generateObjectScopeAccesses($contact, Role::SCOPE_ACCOUNT));

        // Sharable with extra scopes attached
        $sharableContact = new SharableContact("Me", "Test", "1 The Lane", null, null, null, "OX3 2WR", "GB", null, null, 2, null, [
            new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 3, "TEST"),
            new ObjectScopeAccess(Role::SCOPE_PROJECT, "HELLO", "TEST")
        ]);

        $this->assertEquals([new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 2, "OWNER", true, true),
            new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 3, "TEST")], $this->scopeManager->generateObjectScopeAccesses($sharableContact, Role::SCOPE_ACCOUNT));


    }


}