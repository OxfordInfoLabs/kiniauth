<?php


namespace Kiniauth\Test\Services\Application;

use Kiniauth\Objects\Application\Activity;
use Kiniauth\Services\Application\ActivityLogger;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;

include_once "autoloader.php";

class ActivityLoggerTest extends TestBase {

    public function setUp(): void {
        /**
         * @var DatabaseConnection $databaseConnection
         */
        $databaseConnection = Container::instance()->get(DatabaseConnection::class);
        $databaseConnection->query("DELETE FROM ka_activity");

    }


    public function testCanLogAnonymousActivityWhenNoLoggedInUser() {

        AuthenticationHelper::logout();

        ActivityLogger::log("Anonymous Activity", null, null, ["IPAddress" => "186.44.55.34"]);

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $logs = Activity::filter("ORDER BY id");
        $this->assertEquals(2, sizeof($logs));
        $this->assertEquals("Anonymous Activity", $logs[0]->getEvent());
        $this->assertNull($logs[0]->getUserId());
        $this->assertNull( $logs[0]->getAccountId());
        $this->assertNull($logs[0]->getAssociatedObjectId());
        $this->assertNull($logs[0]->getAssociatedObjectDescription());
        $this->assertEquals(["IPAddress" => "186.44.55.34"], $logs[0]->getData());
        $this->assertNotNull($logs[0]->getTimestamp());
        $this->assertNull($logs[0]->getLoggedInSecurableType());
        $this->assertNull($logs[0]->getLoggedInSecurableId());


    }


    public function testCanLogSimpleActivityAsLoggedInUser() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        ActivityLogger::log("User Activity");
        $logs = Activity::filter("ORDER BY id DESC");
        $this->assertEquals(2, sizeof($logs));
        $this->assertEquals("User Activity", $logs[0]->getEvent());
        $this->assertEquals(2, $logs[0]->getUserId());
        $this->assertEquals(1, $logs[0]->getAccountId());
        $this->assertNull($logs[0]->getAssociatedObjectId());
        $this->assertNull($logs[0]->getAssociatedObjectDescription());
        $this->assertEquals([], $logs[0]->getData());
        $this->assertNotNull($logs[0]->getTimestamp());
        $this->assertEquals("USER", $logs[0]->getLoggedInSecurableType());
        $this->assertEquals(2, $logs[0]->getLoggedInSecurableId());



    }


    public function testCanLogAdvancedActivityAsLoggedInUser() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        ActivityLogger::log("User Activity", 25, "My Test Site", ["siteKey" => "BINGO"]);
        $logs = Activity::filter("ORDER BY id DESC");
        $this->assertEquals(2, sizeof($logs));
        $this->assertEquals("User Activity", $logs[0]->getEvent());
        $this->assertEquals(2, $logs[0]->getUserId());
        $this->assertEquals(1, $logs[0]->getAccountId());
        $this->assertEquals(25, $logs[0]->getAssociatedObjectId());
        $this->assertEquals("My Test Site", $logs[0]->getAssociatedObjectDescription());
        $this->assertEquals(["siteKey" => "BINGO"], $logs[0]->getData());
        $this->assertNotNull($logs[0]->getTimestamp());
        $this->assertEquals("USER", $logs[0]->getLoggedInSecurableType());
        $this->assertEquals(2, $logs[0]->getLoggedInSecurableId());


    }


    public function testCanLogAdvancedActivityForOtherUserOrAccount() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        ActivityLogger::log("User Activity", 25, "My Test Site", ["siteKey" => "BINGO"], 5, null, "TRANSACTION1");
        ActivityLogger::log("Other Activity", 25, "My Test Site", ["siteKey" => "BINGO"], null, 7, "TRANSACTION2");


        $logs = Activity::filter("ORDER BY id DESC");
        $this->assertEquals(3, sizeof($logs));


        $this->assertEquals("Other Activity", $logs[0]->getEvent());
        $this->assertEquals(null, $logs[0]->getUserId());
        $this->assertEquals(7, $logs[0]->getAccountId());
        $this->assertEquals(25, $logs[0]->getAssociatedObjectId());
        $this->assertEquals("My Test Site", $logs[0]->getAssociatedObjectDescription());
        $this->assertEquals(["siteKey" => "BINGO"], $logs[0]->getData());
        $this->assertNotNull($logs[0]->getTimestamp());
        $this->assertEquals("USER", $logs[0]->getLoggedInSecurableType());
        $this->assertEquals(1, $logs[0]->getLoggedInSecurableId());
        $this->assertEquals("TRANSACTION2", $logs[0]->getTransactionId());


        $this->assertEquals("User Activity", $logs[1]->getEvent());
        $this->assertEquals(5, $logs[1]->getUserId());
        $this->assertEquals(null, $logs[1]->getAccountId());
        $this->assertEquals(25, $logs[1]->getAssociatedObjectId());
        $this->assertEquals("My Test Site", $logs[1]->getAssociatedObjectDescription());
        $this->assertEquals(["siteKey" => "BINGO"], $logs[1]->getData());
        $this->assertNotNull($logs[1]->getTimestamp());
        $this->assertEquals("USER", $logs[1]->getLoggedInSecurableType());
        $this->assertEquals(1, $logs[1]->getLoggedInSecurableId());
        $this->assertEquals("TRANSACTION1", $logs[1]->getTransactionId());

    }


}
