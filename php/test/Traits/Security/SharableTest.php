<?php

namespace Kiniauth\Test\Traits\Security;

use Kiniauth\Objects\Security\ObjectScopeAccess;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;

include_once "autoloader.php";

class SharableTest extends TestBase {

    public function setUp(): void {
        $databaseConnection = Container::instance()->get(DatabaseConnection::class);
        $databaseConnection->query("DROP TABLE IF EXISTS test_sharable");
        $databaseConnection->query("CREATE TABLE test_sharable (id INTEGER AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))");
    }


    public function testSharingAppliedCorrectlyToObjectUsingTrait() {

        $sharable = new TestSharable(1, "Person 1");
        $sharable->save();

        (new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 1, "TESTGROUP" , false, false, null, TestSharable::class, $sharable->getId()))->save();
        (new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 2, "TESTGROUP2", false, false, null, TestSharable::class, $sharable->getId()))->save();


        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $reSharable = TestSharable::fetch(1);
        $this->assertEquals("Person 1", $reSharable->getName());

//        $accesses = $reSharable->returnValidObjectScopeAccesses(Role::SCOPE_ACCOUNT);
//
//        $this->assertEquals(2, sizeof($accesses));
//        $this->assertEquals(["ACCOUNT", 1], [$accesses[0]->getRecipientScope(), $accesses[0]->getRecipientPrimaryKey()]);
//        $this->assertEquals(["ACCOUNT", 2], [$accesses[1]->getRecipientScope(), $accesses[1]->getRecipientPrimaryKey()]);

    }

}