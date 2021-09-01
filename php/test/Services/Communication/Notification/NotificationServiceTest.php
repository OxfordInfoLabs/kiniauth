<?php


namespace Kiniauth\Test\Services\Communication\Notification;

use Kiniauth\Objects\Communication\Notification\Notification;
use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupMember;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Communication\Notification\NotificationSummary;
use Kiniauth\Objects\Communication\Notification\UserNotification;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserCommunicationData;
use Kiniauth\Services\Communication\Notification\CommunicationMethod\NotificationCommunicationMethod;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;

include_once __DIR__ . "/../../../autoloader.php";

class NotificationServiceTest extends TestBase {

    /**
     * @var NotificationService
     */
    private $notificationService;


    public function setUp(): void {
        $this->notificationService = new NotificationService();
    }

    public function testCanCreateReadUpdateAndDeleteNotificationGroups() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $notificationGroupSummary = new NotificationGroupSummary("My Group", [
            new NotificationGroupMember(new UserCommunicationData(2)),
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
            new NotificationGroupMember(new UserCommunicationData(2, "Sam Davis", "sam@samdavisdesign.co.uk", "07891 147676"), null, $member1Id),
            new NotificationGroupMember(null, "james@test.com", $member2Id)
        ], NotificationGroup::COMMUNICATION_METHOD_INTERNAL_ONLY, $newId), $reGroup);

        // Add a couple more
        $this->notificationService->saveNotificationGroup(new NotificationGroupSummary("My Second Group", [
            new NotificationGroupMember(new UserCommunicationData(2)),
            new NotificationGroupMember(null, "james@test.com")
        ]), null, 1);

        $this->notificationService->saveNotificationGroup(new NotificationGroupSummary("A different group", [
            new NotificationGroupMember(new UserCommunicationData(3))
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


    public function testCanCreateSimpleInternalOnlyNotificationForUserId() {

        $notification = new NotificationSummary("General Notification", "This is a general notification",
            new UserCommunicationData(1));

        $notificationId = $this->notificationService->createNotification($notification, null, 1);

        // Check notification has been created
        $this->assertNotNull($notificationId);

        $reNotification = Notification::fetch($notificationId);
        $this->assertNotNull($reNotification->getCreatedDate());
        $this->assertEquals("General Notification", $reNotification->getTitle());

        // Get user notification
        $userNotification = UserNotification::fetch([$notificationId, 1]);
        $this->assertFalse($userNotification->isRead());

    }


    public function testCanCreateSimpleInternalOnlyNotificationForNotificationGroup() {


        $notificationGroup = new NotificationGroupSummary("New Group",
            [
                new NotificationGroupMember(new UserCommunicationData(1)),
                new NotificationGroupMember(new UserCommunicationData(10)),
                new NotificationGroupMember(new UserCommunicationData(11))
            ]);

        $groupId = $this->notificationService->saveNotificationGroup($notificationGroup, null, 1);

        $notification = new NotificationSummary("General Group Notification", "This is a general notification",
            null, [
                new NotificationGroupSummary("Test Group", null, NotificationGroupSummary::COMMUNICATION_METHOD_INTERNAL_ONLY, $groupId)]);

        $notificationId = $this->notificationService->createNotification($notification, null, 1);

        // Check notification has been created
        $this->assertNotNull($notificationId);

        $reNotification = Notification::fetch($notificationId);
        $this->assertNotNull($reNotification->getCreatedDate());
        $this->assertEquals("General Group Notification", $reNotification->getTitle());

        // Get user notification
        $userNotification = UserNotification::fetch([$notificationId, 1]);
        $this->assertFalse($userNotification->isRead());

        $userNotification = UserNotification::fetch([$notificationId, 10]);
        $this->assertFalse($userNotification->isRead());

        $userNotification = UserNotification::fetch([$notificationId, 11]);
        $this->assertFalse($userNotification->isRead());

    }


    public function testCanCreateNotificationWithCommunicationMethodForGroups() {

        $testCommunicationMethod = MockObjectProvider::instance()->getMockInstance(NotificationCommunicationMethod::class);

        Container::instance()->addInterfaceImplementation(NotificationCommunicationMethod::class,
            "test", $testCommunicationMethod);


        $notificationGroup = new NotificationGroupSummary("New Group",
            [
                new NotificationGroupMember(new UserCommunicationData(2)),
                new NotificationGroupMember(null, "group@test.com")
            ], "test");

        $groupId = $this->notificationService->saveNotificationGroup($notificationGroup, null, 1);

        $notification = new NotificationSummary("External Group Notification", "This is an external notification",
            null, [
                new NotificationGroupSummary("Test Group", null, NotificationGroupSummary::COMMUNICATION_METHOD_INTERNAL_ONLY, $groupId)]);

        $notificationId = $this->notificationService->createNotification($notification, null, 1);

        // Check notification has been created
        $this->assertNotNull($notificationId);

        $reNotification = Notification::fetch($notificationId);
        $this->assertNotNull($reNotification->getCreatedDate());
        $this->assertEquals("External Group Notification", $reNotification->getTitle());

        // Check user notification
        $userNotification = UserNotification::fetch([$notificationId, 2]);
        $this->assertFalse($userNotification->isRead());


        // Now check that the communication method was called with appropriate parameters.
        $reGroup = NotificationGroup::fetch($groupId);
        $this->assertTrue($testCommunicationMethod->methodWasCalled("processNotification", [
            $reNotification, $reGroup->getMembers()
        ]));

    }

    public function testCanGetTotalUnreadNotificationsForUserId() {

        // Assume zero initially
        $this->assertEquals(0, $this->notificationService->getUnreadUserNotificationCount(3));

        $this->notificationService->createNotification(new NotificationSummary("General Notification", "This is a general notification",
            new UserCommunicationData(3)), null, 2);

        $this->assertEquals(1, $this->notificationService->getUnreadUserNotificationCount(3));

        $this->notificationService->createNotification(new NotificationSummary("Second General Notification", "This is a general notification",
            new UserCommunicationData(3)), null, 2);

        $this->assertEquals(2, $this->notificationService->getUnreadUserNotificationCount(3));

    }


    public function testCanMarkUserNotificationsAsReadOrUnread() {

        $id1 = $this->notificationService->createNotification(new NotificationSummary("General Notification", "This is a general notification",
            new UserCommunicationData(4)), null, 3);

        $id2 = $this->notificationService->createNotification(new NotificationSummary("General Notification", "This is a general notification",
            new UserCommunicationData(4)), null, 3);


        $this->assertEquals(2, $this->notificationService->getUnreadUserNotificationCount(4));


        $this->notificationService->markUserNotifications([
            $id1, $id2
        ], true, 4);

        $this->assertEquals(0, $this->notificationService->getUnreadUserNotificationCount(4));

        $this->notificationService->markUserNotifications([
            $id2
        ], false, 4);

        $this->assertEquals(1, $this->notificationService->getUnreadUserNotificationCount(4));

    }

    public function testCanListNotificationsForUserAccountAndProjects() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $firstNotificationId = $this->notificationService->createNotification(new NotificationSummary("General Notification", "This is a general notification",
            new UserCommunicationData(2)), null, 1);

        $secondNotificationId = $this->notificationService->createNotification(new NotificationSummary("Additional Notification", "This is a general notification",
            new UserCommunicationData(2)), null, 1);

        $thirdNotificationId = $this->notificationService->createNotification(new NotificationSummary("Super Notification", "This is a general notification",
            new UserCommunicationData(2)), null, 1);


        $fourthNotificationId = $this->notificationService->createNotification(new NotificationSummary("Other User Notification", "This is a general notification",
            new UserCommunicationData(10)), "soapSuds", 1);

        $fifthNotificationId = $this->notificationService->createNotification(new NotificationSummary("Additional Other User Notification", "This is a general notification",
            new UserCommunicationData(10)), "wiperBlades", 1);


        // Full list
        $notifications = $this->notificationService->listNotifications(25, 0, null, 1, 2);
        $this->assertGreaterThan(2, sizeof($notifications));

        $this->assertEquals(UserNotification::fetch([$thirdNotificationId, 2])->returnSummary(), $notifications[0]);
        $this->assertEquals(UserNotification::fetch([$secondNotificationId, 2])->returnSummary(), $notifications[1]);
        $this->assertEquals(UserNotification::fetch([$firstNotificationId, 2])->returnSummary(), $notifications[2]);


        // Limited list
        $notifications = $this->notificationService->listNotifications(2, 0, null, 1, 2);
        $this->assertEquals(2, sizeof($notifications));

        $this->assertEquals(UserNotification::fetch([$thirdNotificationId, 2])->returnSummary(), $notifications[0]);
        $this->assertEquals(UserNotification::fetch([$secondNotificationId, 2])->returnSummary(), $notifications[1]);

        // Offset list
        $notifications = $this->notificationService->listNotifications(2, 1, null, 1, 2);
        $this->assertEquals(2, sizeof($notifications));

        $this->assertEquals(UserNotification::fetch([$secondNotificationId, 2])->returnSummary(), $notifications[0]);
        $this->assertEquals(UserNotification::fetch([$firstNotificationId, 2])->returnSummary(), $notifications[1]);


        // Other user
        $notifications = $this->notificationService->listNotifications(25, 0, null, 1, 10);
        $this->assertGreaterThan(1, sizeof($notifications));

        $this->assertEquals(UserNotification::fetch([$fifthNotificationId, 10])->returnSummary(), $notifications[0]);
        $this->assertEquals(UserNotification::fetch([$fourthNotificationId, 10])->returnSummary(), $notifications[1]);

        // Project limited
        $notifications = $this->notificationService->listNotifications(25, 0, "soapSuds", 1, 10);
        $this->assertEquals(1, sizeof($notifications));
        $this->assertEquals(UserNotification::fetch([$fourthNotificationId, 10])->returnSummary(), $notifications[0]);

        $notifications = $this->notificationService->listNotifications(25, 0, "wiperBlades", 1, 10);
        $this->assertEquals(1, sizeof($notifications));
        $this->assertEquals(UserNotification::fetch([$fifthNotificationId, 10])->returnSummary(), $notifications[0]);

    }

    public function testInitiallyFlaggedNotificationsArePinnedToTheTopOfListIfUnread() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $firstNotificationId = $this->notificationService->createNotification(new NotificationSummary("Flagged Notification", "This is a flagged notification",
            new UserCommunicationData(11), null, null, null, NotificationSummary::STATE_FLAGGED), null, 1);

        $secondNotificationId = $this->notificationService->createNotification(new NotificationSummary("Additional Notification", "This is a general notification",
            new UserCommunicationData(11)), null, 1);

        $thirdNotificationId = $this->notificationService->createNotification(new NotificationSummary("Super Notification", "This is a general notification",
            new UserCommunicationData(11)), null, 1);


        // Now check that flagged one is at top until read
        $notifications = $this->notificationService->listNotifications(25, 0, null, 1, 11);
        $this->assertGreaterThan(2, sizeof($notifications));

        $this->assertEquals(UserNotification::fetch([$firstNotificationId, 11])->returnSummary(), $notifications[0]);
        $this->assertEquals(UserNotification::fetch([$thirdNotificationId, 11])->returnSummary(), $notifications[1]);
        $this->assertEquals(UserNotification::fetch([$secondNotificationId, 11])->returnSummary(), $notifications[2]);

        // Now mark it as read and check order changes
        $this->notificationService->markUserNotifications([$firstNotificationId], true, 11);

        $notifications = $this->notificationService->listNotifications(25, 0, null, 1, 11);
        $this->assertGreaterThan(2, sizeof($notifications));

        $this->assertEquals(UserNotification::fetch([$thirdNotificationId, 11])->returnSummary(), $notifications[0]);
        $this->assertEquals(UserNotification::fetch([$secondNotificationId, 11])->returnSummary(), $notifications[1]);
        $this->assertEquals(UserNotification::fetch([$firstNotificationId, 11])->returnSummary(), $notifications[2]);

    }


}