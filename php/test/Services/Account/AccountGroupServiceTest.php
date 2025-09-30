<?php

namespace Kiniauth\Test\Services\Account;

use Kiniauth\Exception\Security\InvalidAccountGroupOwnerException;
use Kiniauth\Objects\Account\AccountGroup;
use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Objects\Communication\Email\AccountTemplatedEmail;
use Kiniauth\Objects\Workflow\PendingAction;
use Kiniauth\Services\Account\AccountGroupService;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\Account\AccountGroupDescriptor;
use Kiniauth\ValueObjects\Account\AccountGroupInvitation;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

class AccountGroupServiceTest extends TestBase {

    private $accountGroupService;

    private $emailService;

    private $pendingActionService;

    private $securityService;

    public function setUp(): void {
        $this->emailService = MockObjectProvider::instance()->getMockInstance(EmailService::class);
        $this->pendingActionService = MockObjectProvider::instance()->getMockInstance(PendingActionService::class);
        $this->accountGroupService = new AccountGroupService($this->emailService, $this->pendingActionService,
            Container::instance()->get(ActiveRecordInterceptor::class));
        $this->securityService = Container::instance()->get(SecurityService::class);
    }

    public function testCanGetAccountGroups() {
        $accountGroups = $this->accountGroupService->getAllAccountGroups();

        $this->assertCount(2, $accountGroups);
        $this->assertEquals("First Account Group", $accountGroups[0]->getName());
        $this->assertEquals("Second Account Group", $accountGroups[1]->getName());

        $this->assertCount(2, $accountGroups[0]->getAccountGroupMembers());
        $this->assertCount(3, $accountGroups[1]->getAccountGroupMembers());
    }

    public function testCanGetMembersOfAccountGroup() {
        $groupOneMembers = $this->accountGroupService->getMembersOfAccountGroup(1);
        $groupTwoMembers = $this->accountGroupService->getMembersOfAccountGroup(2);

        $this->assertCount(2, $groupOneMembers);
        $this->assertCount(3, $groupTwoMembers);
    }

    public function testCanListAccountGroupsForAccount() {
        $accountGroups = $this->accountGroupService->listAccountGroupsForAccount(1);
        $this->assertCount(2, $accountGroups);

        $accountGroups = $this->accountGroupService->listAccountGroupsForAccount(2);
        $this->assertCount(2, $accountGroups);

        $accountGroups = $this->accountGroupService->listAccountGroupsForAccount(3);
        $this->assertCount(1, $accountGroups);
    }

    public function testCanCreateNewAccountGroup() {

        $accountGroupId = $this->accountGroupService->createAccountGroup(
            new AccountGroupDescriptor(
                "New Account Group",
                "bestest account group",
                1
            )
        );

        /** @var AccountGroup $accountGroup */
        $accountGroup = AccountGroup::fetch($accountGroupId);

        $this->assertEquals("New Account Group", $accountGroup->getName());
        $this->assertEquals("bestest account group", $accountGroup->getDescription());
        $this->assertEquals(1, $accountGroup->getOwnerAccountId());
    }

    public function testCanAddMembersToAccountGroup() {
        $this->accountGroupService->addMemberToAccountGroup(1, 4);

        try {
            AccountGroupMember::fetch([1, 4]);
            $this->assertTrue(true);
        } catch (ObjectNotFoundException) {
            $this->fail("Couldn't find object");
        }

        try {
            $this->accountGroupService->addMemberToAccountGroup(10, 1);
            $this->fail("Should've thrown here");
        } catch (ObjectNotFoundException) {
            $this->assertTrue(true);
        }
    }

    public function testCanRemoveMembersFromAccountGroup() {
        // Remove someone
        $this->accountGroupService->removeMemberFromAccountGroup(2, 3);

        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(2);
        $this->assertCount(2, $accountGroupMembers);

        // Remove someone else
        $this->accountGroupService->removeMemberFromAccountGroup(2, 1);

        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(2);
        $this->assertCount(1, $accountGroupMembers);
    }

    public function testCanInviteAccountToJoinAccountGroupAndInvitationCanBeAccepted() {

        // Test the invite
        $this->pendingActionService->returnValue("createPendingAction", "mycode123", [
            "ACCOUNT_GROUP_INVITE",
            1,
            ["accountId" => 4]
        ]);

        try {
            $this->accountGroupService->inviteAccountToAccountGroup(1, 4, 4);
        } catch (InvalidAccountGroupOwnerException $e) {
            $this->assertEquals("The logged in account doesn't own the account group", $e->getMessage());
        }

        // Become the account
        $this->securityService->becomeAccount(1);

        $this->accountGroupService->inviteAccountToAccountGroup(1, 4, 1);

        $this->securityService->becomeAccount(4);

        $invitationEmail = new AccountTemplatedEmail(4, "security/account-group-invite", [
            "accountGroup" => AccountGroup::fetch(1),
            "invitationCode" => "mycode123"
        ]);


        $this->assertTrue($this->emailService->methodWasCalled("send", [$invitationEmail, 4]));

        // Test accepting
        $pendingAction = new PendingAction("ACCOUNT_GROUP_INVITE", 1, ["account_id" => 4]);
        $this->pendingActionService->returnValue("getPendingActionByIdentifier", $pendingAction, ["ACCOUNT_GROUP_INVITE", "mycode123"]);
        $this->accountGroupService->acceptAccountGroupInvitation("mycode123");

        try {
            AccountGroupMember::fetch([1, 4]);
            $this->assertTrue(true);
        } catch (ObjectNotFoundException) {
            $this->fail("Object should exist");
        }

        $this->assertTrue($this->pendingActionService->methodWasCalled("removePendingAction", ["ACCOUNT_GROUP_INVITE", "mycode123"]));

        $acceptEmail = new AccountTemplatedEmail(4, "security/account-group-welcome", []);
        $this->assertTrue($this->emailService->methodWasCalled("send", [$acceptEmail, 4]));

    }

    public function testCanListActiveAccountGroupInvitations() {

        $this->pendingActionService->returnValue("getAllPendingActionsForTypeAndObjectId",
            [new PendingAction("ACCOUNT_GROUP_INVITE", 1, ["account_id" => 1])],
            ["ACCOUNT_GROUP_INVITE", 1]);

        $this->accountGroupService->getActiveAccountGroupInvitationAccounts(1);

        $this->assertTrue($this->pendingActionService->methodWasCalled("getAllPendingActionsForTypeAndObjectId", [
            "ACCOUNT_GROUP_INVITE", 1
        ]));
    }

    public function testCanResendAccountGroupInvitation() {
        $pendingActions = [
            new PendingAction("ACCOUNT_GROUP_INVITE", 1, ["accountId" => 5]),
            new PendingAction("ACCOUNT_GROUP_INVITE", 1, ["accountId" => 6])
        ];
        $this->pendingActionService->returnValue("getAllPendingActionsForTypeAndObjectId", $pendingActions, ["ACCOUNT_GROUP_INVITE", 1]);

        $this->securityService->becomeAccount(1);

        $this->accountGroupService->resendAccountGroupInvitationEmail(new AccountGroupInvitation(1, 5));

        $invitationEmail = new AccountTemplatedEmail(5, "security/account-group-invite", [
            "account_group" => AccountGroup::fetch(1),
            "invitationCode" => $pendingActions[0]->getIdentifier(),
            "resent" => true,
            "currentTime" => date("d/m/Y H:i:s")
        ]);

        $this->assertTrue($this->emailService->methodWasCalled("send", [$invitationEmail, 5]));
    }

    public function testCanGetAccountGroupInvitationDetails() {
        $pendingAction = new PendingAction("ACCOUNT_GROUP_INVITE", 2, ["account_id" => 3]);
        $code = $pendingAction->getIdentifier();

        $this->pendingActionService->returnValue("getPendingActionByIdentifier", $pendingAction, ["ACCOUNT_GROUP_INVITE", $code]);

        $invitationDetails = $this->accountGroupService->getInvitationDetails($code);

        $this->assertEquals(2, $invitationDetails->getAccountGroupId());
        $this->assertEquals(3, $invitationDetails->getAccountId());
    }

}