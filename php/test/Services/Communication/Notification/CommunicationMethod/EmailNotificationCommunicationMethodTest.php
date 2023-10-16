<?php


namespace Kiniauth\Test\Services\Communication\Notification\CommunicationMethod;


use Kiniauth\Objects\Communication\Email\BrandedTemplatedEmail;
use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Objects\Communication\Notification\Notification;
use Kiniauth\Objects\Communication\Notification\NotificationGroupMember;
use Kiniauth\Objects\Communication\Notification\NotificationSummary;
use Kiniauth\Objects\Security\UserCommunicationData;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Communication\Notification\CommunicationMethod\EmailNotificationCommunicationMethod;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;

class EmailNotificationCommunicationMethodTest extends TestBase {

    /**
     * @var EmailNotificationCommunicationMethod
     */
    private $communicationMethod;

    /**
     * @var MockObject
     */
    private $emailService;


    public function setUp(): void {
        $this->emailService = MockObjectProvider::instance()->getMockInstance(EmailService::class);
        $this->communicationMethod = new EmailNotificationCommunicationMethod($this->emailService);
    }

    public function testEmailSentUsingNotificationTemplateToEachGroupMemberSupplied() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $notification = new Notification(new NotificationSummary("Test Notification", "Example notification content"), null, 55);

        $groupMembers = [
            new NotificationGroupMember(new UserCommunicationData(2, "Joe Bloggs", "joe.bloggs@test.com")),
            new NotificationGroupMember(new UserCommunicationData(10, "Jane Bloggs", "jane.bloggs@test.com")),
            new NotificationGroupMember(null, "group@test.com")
        ];

        $this->communicationMethod->processNotification($notification, $groupMembers);


        $this->assertTrue($this->emailService->methodWasCalled("send", [
            new UserTemplatedEmail(2, "notification/notification", [
                "notification" => $notification
            ]), 55, 2]));

        $this->assertTrue($this->emailService->methodWasCalled("send", [
            new UserTemplatedEmail(10, "notification/notification", [
                "notification" => $notification
            ]), 55, 10]));

        $this->assertTrue($this->emailService->methodWasCalled("send", [
            new BrandedTemplatedEmail("notification/notification", [
                "notification" => $notification
            ], 55, null, "group@test.com"), 55]));

    }


}