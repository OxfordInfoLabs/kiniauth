<?php


namespace Kiniauth\Test\Services\Notification;

use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupMember;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Security\UserLabel;
use Kiniauth\Services\Notification\NotificationService;
use Kiniauth\Test\TestBase;

include_once __DIR__ . "/../../autoloader.php";

class NotificationServiceTest extends TestBase {

    /**
     * @var NotificationService
     */
    private $notificationService;


    public function setUp(): void {
        $this->notificationService = new NotificationService();
    }

    public function testCanCreateReadUpdateAndDeleteNotificationGroups() {

        $notificationGroupSummary = new NotificationGroupSummary("My Group", [
            new NotificationGroupMember(new UserLabel(2)),
            new NotificationGroupMember(null, "james@test.com")
        ]);

        $newId = $this->notificationService->saveNotificationGroup($notificationGroupSummary, null, 1);
        $this->assertNotNull($newId);
        $member1Id = $notificationGroupSummary->getMembers()[0]->getId();
        $this->assertNotNull($member1Id);
        $member2Id = $notificationGroupSummary->getMembers()[1]->getId();
        $this->assertNotNull($member2Id);

        // Get group back
        $reGroup = $this->notificationService->getNotificationGroup($newId);
        $this->assertEquals(new NotificationGroupSummary("My Group", [
            new NotificationGroupMember(new UserLabel(2, "Sam Davis"), null, $member1Id),
            new NotificationGroupMember(null, "james@test.com", $member2Id)
        ], NotificationGroup::COMMUNICATION_METHOD_INTERNAL_ONLY, $newId), $reGroup);

        // Add a couple more
        $this->notificationService->saveNotificationGroup(new NotificationGroupSummary("My Second Group", [
            new NotificationGroupMember(new UserLabel(2)),
            new NotificationGroupMember(null, "james@test.com")
        ]), null, 1);

        $this->notificationService->saveNotificationGroup(new NotificationGroupSummary("A different group", [
            new NotificationGroupMember(new UserLabel(3))
        ]), null, 2);


        // List all notification groups
        $groups = $this->notificationService->listNotificationGroups(25, 0, null, 1);
        $this->assertEquals(2, sizeof($groups));
        $this->assertNotNull($groups[0]->getId());
        $this->assertEquals("My Group", $groups[0]->getName());
        $this->assertEquals($this->notificationService->getNotificationGroup($groups[0]->getId()), $groups[0]);
        $this->assertEquals($this->notificationService->getNotificationGroup($groups[1]->getId()), $groups[1]);

        $groups = $this->notificationService->listNotificationGroups(25, 0, null, 2);
        $this->assertEquals(1, sizeof($groups));
        $this->assertNotNull($groups[0]->getId());
        $this->assertEquals("A different group", $groups[0]->getName());
        $this->assertEquals($this->notificationService->getNotificationGroup($groups[0]->getId()), $groups[0]);

        // Remove groups
        $this->notificationService->removeNotificationGroup($newId);
        $groups = $this->notificationService->listNotificationGroups(25, 0, null, 1);
        $this->assertEquals(1, sizeof($groups));
        $this->assertNotNull($groups[0]->getId());
        $this->assertEquals("My Second Group", $groups[0]->getName());
        $this->assertEquals($this->notificationService->getNotificationGroup($groups[0]->getId()), $groups[0]);


    }

}