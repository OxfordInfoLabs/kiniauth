<?php

namespace Kiniauth\Test\Services\Account;

use Exception;
use Kiniauth\Exception\Security\AccountAlreadyAttachedToAccountGroupException;
use Kiniauth\Exception\Security\AccountGroupOwnerCannotLeaveException;
use Kiniauth\Exception\Security\AccountGroupOwnerCannotRemoveThemselvesException;
use Kiniauth\Exception\Security\InvalidAccountGroupOwnerException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountGroup;
use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Objects\Account\AccountLabel;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Communication\Email\AccountTemplatedEmail;
use Kiniauth\Objects\Workflow\PendingAction;
use Kiniauth\Services\Account\AccountGroupService;
use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\Account\AccountGroupDescriptor;
use Kinikit\Core\Communication\Email\MissingEmailTemplateException;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Throwable;

class AccountGroupServiceTest extends TestBase {

    private AccountGroupService $accountGroupService;
    private EmailService|MockObject $emailService;
    private PendingActionService|MockObject $pendingActionService;
    private SecurityService $securityService;
    private AccountService|MockObject $accountService;
    private static ?array $originalAccountGroupSnapshot = null;


    public function setUp(): void {

        // Record the original test account groups from test data.
        if (self::$originalAccountGroupSnapshot === null) {

            self::$originalAccountGroupSnapshot = [];

            $accountGroups = AccountGroup::filter();

            foreach ($accountGroups as $accountGroup) {

                $memberAccountIds = array_map(
                    fn(AccountGroupMember $member) => $member->getMemberAccountId(),
                    $accountGroup->getAccountGroupMembers()
                );

                self::$originalAccountGroupSnapshot[$accountGroup->getId()] = $memberAccountIds;
            }
        }

        $fileResolver = Container::instance()->get(FileResolver::class);
        $fileResolver->addSearchPath(__DIR__ . "/../../../src");

        $this->emailService = MockObjectProvider::instance()->getMockInstance(EmailService::class);
        $this->pendingActionService = MockObjectProvider::instance()->getMockInstance(
            PendingActionService::class
        );
        $this->accountService = MockObjectProvider::instance()->getMockInstance(AccountService::class);

        $this->accountGroupService = new AccountGroupService($this->emailService, $this->pendingActionService,
            Container::instance()->get(ActiveRecordInterceptor::class), $this->accountService);
        $this->securityService = Container::instance()->get(SecurityService::class);

        $this->securityService->logout();
    }

    public function tearDown(): void {

        try {
            if (self::$originalAccountGroupSnapshot !== null) {

                $this->securityService->becomeSuperUser();

                $originalGroupIds = array_keys(self::$originalAccountGroupSnapshot);

                // Remove any account groups created during the tests.
                $existingAccountGroups = AccountGroup::filter();

                foreach ($existingAccountGroups as $accountGroup) {

                    if (!in_array($accountGroup->getId(), $originalGroupIds)) {
                        $accountGroup->remove();
                    }
                }

                // Restore original account group memberships.
                foreach (self::$originalAccountGroupSnapshot as $groupId => $memberAccountIds) {

                    /** @var AccountGroup $accountGroup */
                    $accountGroup = AccountGroup::fetch($groupId);

                    $members = [];

                    foreach ($memberAccountIds as $memberAccountId) {
                        $members[] = new AccountGroupMember($groupId, $memberAccountId);
                    }

                    $accountGroup->setAccountGroupMembers($members);
                    $accountGroup->save();
                }

                $this->securityService->logout();
            }
        } finally {
            parent::tearDown();
        }
    }

    public function testCanGetAccountGroups() {
        $accountGroups = $this->accountGroupService->getAllAccountGroups();

        $this->assertCount(2, $accountGroups);
        $this->assertEquals("First Account Group", $accountGroups[0]->getName());
        $this->assertEquals("Second Account Group", $accountGroups[1]->getName());

        $this->assertCount(2, $accountGroups[0]->getAccountGroupMembers());
        $this->assertCount(3, $accountGroups[1]->getAccountGroupMembers());
    }

    /**
     * @throws AccessDeniedException
     */
    public function testCanRetrieveAccountGroupAsAMember(): void {

        $accountGroup = $this->accountGroupService->getAccountGroup(1, 1);

        $this->assertEquals(1, $accountGroup->getId());
        $this->assertEquals("First Account Group", $accountGroup->getName());
        $this->assertEquals("description", $accountGroup->getDescription());
        $this->assertEquals(1, $accountGroup->getOwnerAccountId());

        $this->assertCount(2, $accountGroup->getAccountGroupMembers());
    }

    public function testCannotGetAccountGroupThatAUserIsNotAPartOf() {

        $this->expectException(AccessDeniedException::class);

        // user 5 is not in any account group
        $this->accountGroupService->getAccountGroup(1, 5);
    }

    /**
     * @throws AccessDeniedException
     */
    public function testCannotGetAccountGroupThatDoesNotExist() {

        $this->expectException(ObjectNotFoundException::class);

        // group 5 does not exist
        $this->accountGroupService->getAccountGroup(5, 1);
    }

    /**
     * @throws AccessDeniedException
     */
    public function testCanGetMembersOfAccountGroup() {
        $groupOneMembers = $this->accountGroupService->getMembersOfAccountGroup(
            1,
            1
        );
        $groupTwoMembers = $this->accountGroupService->getMembersOfAccountGroup(
            2,
            1
        );

        $this->assertCount(2, $groupOneMembers);
        $this->assertCount(3, $groupTwoMembers);
    }

    public function testNonMemberCannotRetrieveAccountGroupMembers(): void {

        $this->expectException(AccessDeniedException::class);

        $this->accountGroupService->getMembersOfAccountGroup(1, 5);
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

        $this->securityService->becomeAccount(1);
        $accountGroupId = $this->accountGroupService->createAccountGroup(
            new AccountGroupDescriptor(
                "New Account Group",
                "bestest account group",
                "group",
                1
            )
        );

        /** @var AccountGroup $accountGroup */
        $accountGroup = AccountGroup::fetch($accountGroupId);

        $this->assertEquals("New Account Group", $accountGroup->getName());
        $this->assertEquals("bestest account group", $accountGroup->getDescription());
        $this->assertEquals("group", $accountGroup->getType());
        $this->assertEquals(1, $accountGroup->getOwnerAccountId());

        // Check we are in the group
        $this->assertEquals(
            [
                new AccountGroupMember(
                    $accountGroupId,
                    1,
                    new AccountLabel(1, "Sam Davis Design")
                )
            ],
            $accountGroup->getAccountGroupMembers()
        );

    }

    /**
     * @throws AccessDeniedException|InvalidAccountGroupOwnerException
     */
    public function testCanDeleteAnAccountGroup() {

        $this->securityService->becomeAccount(1);
        $accountGroupId = $this->accountGroupService->createAccountGroup(
            new AccountGroupDescriptor(
                "New Account Group",
                "bestest account group",
                "group",
                1
            )
        );

        /** @var AccountGroup $accountGroup */
        $accountGroup = AccountGroup::fetch($accountGroupId);

        $this->assertEquals("New Account Group", $accountGroup->getName());
        $this->assertEquals("bestest account group", $accountGroup->getDescription());
        $this->assertEquals("group", $accountGroup->getType());
        $this->assertEquals(1, $accountGroup->getOwnerAccountId());

        // now delete the group
        $this->accountGroupService->deleteAccountGroup($accountGroupId, 1);

        // make sure to see that the group no longer exists
        try {
            AccountGroup::fetch($accountGroupId);
            $this->fail("Account group object still exist");
        }
        catch (ObjectNotFoundException) {}

        // make sure its members were removed as well
        try {
            AccountGroupMember::fetch([$accountGroupId, 1]);
            $this->fail("Account group member object(s) still exist");
        }
        catch (ObjectNotFoundException) {}
    }

    /**
     * @throws AccessDeniedException
     */
    public function testNonOwnerCannotDeleteAccountGroup(): void {

        $this->expectException(InvalidAccountGroupOwnerException::class);

        $this->accountGroupService->deleteAccountGroup(1, 2);
    }

    /**
     * @throws AccessDeniedException
     */
    public function testCanAddMembersToAccountGroup() {
        $this->securityService->becomeAccount(1);
        $this->accountGroupService->addMemberToAccountGroup(1, 4, 1);

        try {
            AccountGroupMember::fetch([1, 4]);
            $this->assertTrue(true);
        } catch (ObjectNotFoundException) {
            $this->fail("Couldn't find object");
        }
    }

    /**
     * @throws AccessDeniedException
     */
    public function testAddingExistingMemberDoesNotCreateDuplicateMembership(): void {

        // user 2 already in account group 1
        $this->accountGroupService->addMemberToAccountGroup(1, 2, 1);

        $members = $this->accountGroupService->getMembersOfAccountGroup(1, 1);

        $memberIds = array_map(
            fn(AccountGroupMember $member) => $member->getMemberAccountId(),
            $members
        );

        $this->assertCount(2, $members);
        $this->assertCount(1, array_filter($memberIds, fn($id) => $id == 2));
    }

    /**
     * @throws AccessDeniedException
     */
    public function testNonOwnerCannotAddMemberToAccountGroup(): void {

        $this->expectException(InvalidAccountGroupOwnerException::class);

        // Account 2 is a member but not the owner of group 1.
        $this->accountGroupService->addMemberToAccountGroup(1, 4, 2);
    }

    /**
     * @throws AccessDeniedException
     */
    public function testCanRemoveMembersFromAccountGroup() {
        // Remove someone
        $this->accountGroupService->removeMemberFromAccountGroup(2, 3, 2);

        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(2, 2);
        $this->assertCount(2, $accountGroupMembers);

        // Remove someone else
        $this->accountGroupService->removeMemberFromAccountGroup(2, 1, 2);

        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(2, 2);
        $this->assertCount(1, $accountGroupMembers);
    }

    /**
     * @throws AccessDeniedException
     */
    public function testRemovingNonExistentMembersFromAccountGroupDoesNothing(): void {

        try {
            // Remove someone
            $this->accountGroupService->removeMemberFromAccountGroup(
                2,
                4,
                2
            );
        }
        catch (Exception $e) {
            $this->fail("Some exception was thrown: " . $e->getMessage());
        }

        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(2, 2);
        $this->assertCount(3, $accountGroupMembers);
    }

    /**
     * @throws AccessDeniedException
     */
    public function testNonOwnerCannotRemoveMemberFromAccountGroup(): void {

        $this->expectException(InvalidAccountGroupOwnerException::class);

        $this->accountGroupService->removeMemberFromAccountGroup(1, 2, 2);
    }

    /**
     * @throws AccessDeniedException
     */
    public function testOwnerCannotRemoveThemselvesFromAccountGroup(): void {

        $this->expectException(AccountGroupOwnerCannotRemoveThemselvesException::class);

        $this->accountGroupService->removeMemberFromAccountGroup(1, 1, 1);
    }

    /**
     * @throws AccessDeniedException
     */
    public function testCanLeaveAccountGroup() {

        // user 3 leaves from group 2
        $this->accountGroupService->leaveAccountGroup(2, 3);

        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(2, 1);
        $this->assertCount(2, $accountGroupMembers);
    }

    /**
     * @throws AccessDeniedException
     */
    public function testNonMembersLeavingAccountGroupDoesThrowsException(): void {

        try {
            // non-member tries to leave account group
            $this->accountGroupService->leaveAccountGroup(
                2,
                4,
            );
            $this->fail("Exception should have been thrown");
        }
        catch (AccessDeniedException) {
        }

        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(2, 2);
        $this->assertCount(3, $accountGroupMembers);
    }

    /**
     * @throws AccessDeniedException
     */
    public function testOwnerCannotLeaveTheirOwnAccountGroup() {

        try {
            $this->accountGroupService->leaveAccountGroup(1, 1);
            $this->fail("Owner shouldn't be able to leave their own group");
        }
        catch (AccountGroupOwnerCannotLeaveException $e) {
            $this->assertEquals("The account group owner cannot leave their own group", $e->getMessage());
        }

        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(1, 1);
        $this->assertCount(2, $accountGroupMembers);
    }

    /**
     * @throws AccessDeniedException
     */
    public function testLeavingAccountGroupRemovesOnlyTheLeavingAccount(): void {

        $this->accountGroupService->leaveAccountGroup(2, 3);

        $members = $this->accountGroupService->getMembersOfAccountGroup(2, 1);

        $memberIds = array_map(
            fn(AccountGroupMember $member) => $member->getMemberAccountId(),
            $members
        );

        $this->assertNotContains(3, $memberIds);
        $this->assertContains(1, $memberIds);
        $this->assertContains(2, $memberIds);
    }

    /**
     * @throws ValidationException
     * @throws MissingEmailTemplateException
     * @throws InvalidAccountGroupOwnerException
     * @throws Throwable
     * @throws AccountAlreadyAttachedToAccountGroupException
     */
    public function testCanInviteAccountToJoinAccountGroupAndInvitationCanBeAccepted() {

        // Test the invite
        $this->pendingActionService->returnValue(
            "createPendingAction",
            "mycode123",
            [
                "ACCOUNT_GROUP_INVITE",
                1,
                ["accountId" => 3]
            ]
        );

        $account = new Account("Test", 0, AccountSummary::STATUS_ACTIVE, 3);
        $this->accountService->returnValue("getAccountByExternalIdentifier", $account, ["APPLEPIE"]);

        $this->accountGroupService->inviteAccountToAccountGroup(
            1,
            "APPLEPIE",
            1
        );

        $this->securityService->becomeSuperUser();
        $invitationEmail = new AccountTemplatedEmail(
            3,
            "security/account-group-invite",
            [
                "accountGroup" => AccountGroup::fetch(1),
                "invitationCode" => "mycode123"
            ]
        );

        $this->assertTrue($this->emailService->methodWasCalled("send", [$invitationEmail, 3]));

        // Test accepting
        $pendingAction = new PendingAction("ACCOUNT_GROUP_INVITE", 1, ["accountId" => 3]);
        $this->pendingActionService->returnValue(
            "getPendingActionByIdentifier",
            $pendingAction, ["ACCOUNT_GROUP_INVITE", "mycode123"]
        );

        $this->securityService->logout();

        $activeRecordInterceptor = Container::instance()->get(ActiveRecordInterceptor::class);
        $activeRecordInterceptor->executeInsecure(function () {
            $this->accountGroupService->acceptAccountGroupInvitation("mycode123", 3);
        });

        try {
            AccountGroupMember::fetch([1, 3]);
            $this->assertTrue(true);
        } catch (ObjectNotFoundException) {
            $this->fail("Object should exist");
        }

        $this->assertTrue(
            $this->pendingActionService->methodWasCalled(
                "removePendingAction", ["ACCOUNT_GROUP_INVITE", "mycode123"]
            )
        );

        $this->securityService->becomeSuperUser();
        $acceptEmail = new AccountTemplatedEmail(3, "security/account-group-welcome", []);
        $this->assertTrue($this->emailService->methodWasCalled("send", [$acceptEmail, 3]));
        $this->securityService->logout();
    }

    /**
     * @throws ValidationException
     * @throws MissingEmailTemplateException
     * @throws InvalidAccountGroupOwnerException
     * @throws Throwable
     * @throws AccountAlreadyAttachedToAccountGroupException
     */
    public function testNonOwnerCannotInviteAccountToJoinAccountGroup() {

        // Test the invite
        $this->pendingActionService->returnValue("createPendingAction", "mycode123", [
            "ACCOUNT_GROUP_INVITE",
            1,
            ["accountId" => 3]
        ]);

        $account = new Account("Test", 0, AccountSummary::STATUS_ACTIVE, 3);
        $this->accountService->returnValue("getAccountByExternalIdentifier", $account, ["APPLEPIE"]);

        try {
            $this->accountGroupService->inviteAccountToAccountGroup(
                1,
                "APPLEPIE",
                2
            );
            $this->fail("Exception should have thrown here");
        } catch (InvalidAccountGroupOwnerException $e) {
            $this->assertEquals("The logged in account doesn't own the account group", $e->getMessage());
        }
    }

    /**
     * @throws ValidationException
     * @throws MissingEmailTemplateException
     * @throws InvalidAccountGroupOwnerException
     * @throws Throwable
     * @throws AccountAlreadyAttachedToAccountGroupException
     */
    public function testInvitingAccountToJoinGroupItIsAlreadyAMemberThrowsAnException() {

        $this->securityService->becomeAccount(3);

        // Test the invite
        $this->pendingActionService->returnValue(
            "createPendingAction",
            "mycode123",
            [
                "ACCOUNT_GROUP_INVITE",
                1,
                ["accountId" => 3]
            ]
        );

        $account = new Account("Test", 0, AccountSummary::STATUS_ACTIVE, 3);
        $this->accountService->returnValue("getAccountByExternalIdentifier", $account, ["APPLEPIE"]);

        // save user 3 to the account
        $accountGroupMember = new AccountGroupMember(1, 3);
        $accountGroupMember->save();

        try {
            $this->accountGroupService->inviteAccountToAccountGroup(
                1,
                "APPLEPIE",
                1
            );
            $this->fail("Exception should have thrown here");
        }
        catch (AccountAlreadyAttachedToAccountGroupException $e) {
            $this->assertEquals(
                "The account Smart Coasting is already attached to this account group",
                $e->getMessage()
            );
        }

        $this->securityService->logout();

        $this->assertFalse($this->pendingActionService->methodWasCalled("createPendingAction"));

        $this->assertFalse($this->emailService->methodWasCalled("send"));
    }

    /**
     * @throws ValidationException
     * @throws MissingEmailTemplateException
     * @throws InvalidAccountGroupOwnerException
     * @throws Throwable
     * @throws AccountAlreadyAttachedToAccountGroupException
     */
    public function testExceptionInEmailServiceRollsBackInviteToJoinAccountGroup() {

        // Test the invite
        $this->pendingActionService->returnValue("createPendingAction", "mycode123", [
            "ACCOUNT_GROUP_INVITE",
            1,
            ["accountId" => 3]
        ]);

        $account = new Account("Test", 0, AccountSummary::STATUS_ACTIVE, 3);
        $this->accountService->returnValue("getAccountByExternalIdentifier", $account, ["APPLEPIE"]);

        // fake an exception from the email service
        $this->emailService->throwException(
            "send",
            new Exception()
        );

        try {
            $this->accountGroupService->inviteAccountToAccountGroup(
                1,
                "APPLEPIE",
                1
            );
            $this->fail("Exception should have thrown here");
        } catch (Exception) {}

        // assert that the invite was rolled back
        $this->assertTrue($this->pendingActionService->methodWasCalled("removePendingAction"));
    }

    /**
     * @throws Throwable|ValidationException
     * @objectInterceptorDisabled
     */
    public function testAcceptingInvitationToAnAccountTheyAreAlreadyInThrowsException(): void {

        $this->securityService->becomeAccount(3);

        $pendingAction = new PendingAction(
            "ACCOUNT_GROUP_INVITE",
            1,
            ["accountId" => 3]
        );

        $code = $pendingAction->getIdentifier();

        $this->pendingActionService->returnValue(
            "getPendingActionByIdentifier",
            $pendingAction,
            ["ACCOUNT_GROUP_INVITE", $code]
        );

        // save user 3 to the account
        $accountGroupMember = new AccountGroupMember(1, 3);
        $accountGroupMember->save();

        // Account 1 attempts to accept an invitation intended for account 3.
        try {
            $this->accountGroupService->acceptAccountGroupInvitation($code, 3);
            $this->fail("Exception should have thrown here");
        }
        catch (AccountAlreadyAttachedToAccountGroupException $e) {
            $this->assertEquals(
                "The account Smart Coasting is already attached to this account group",
                $e->getMessage()
            );
        }

        $this->securityService->logout();

        $this->assertTrue(
            $this->pendingActionService->methodWasCalled(
                "removePendingAction", ["ACCOUNT_GROUP_INVITE", $code]
            )
        );

        $this->assertFalse(
            $this->emailService->methodWasCalled("send")
        );

        // make sure no duplicate members were made
        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(1, 1);

        $memberIds = array_map(
            fn(AccountGroupMember $member) => $member->getMemberAccountId(),
            $accountGroupMembers
        );

        $this->assertCount(3, $memberIds);
        $this->assertCount(1, array_filter($memberIds, fn($id) => $id == 3));
    }

    /**
     * @throws Throwable|ValidationException
     * @objectInterceptorDisabled
     */
    public function testExceptionInEmailServiceStillAcceptsInviteToJoinAccountGroup(): void {

        $this->securityService->becomeAccount(3);

        $pendingAction = new PendingAction(
            "ACCOUNT_GROUP_INVITE",
            1,
            ["accountId" => 3]
        );

        $code = $pendingAction->getIdentifier();

        $this->pendingActionService->returnValue(
            "getPendingActionByIdentifier",
            $pendingAction,
            ["ACCOUNT_GROUP_INVITE", $code]
        );

        // fake an exception from the email service
        $this->emailService->throwException(
            "send",
            new Exception()
        );

        // Account 1 attempts to accept an invitation intended for account 3.
        try {
            $this->accountGroupService->acceptAccountGroupInvitation($code, 3);
            $this->fail("Exception should have thrown here");
        }
        catch (Exception) {}

        $this->securityService->logout();

        $this->assertTrue(
            $this->pendingActionService->methodWasCalled(
                "removePendingAction", ["ACCOUNT_GROUP_INVITE", $code]
            )
        );

        $this->assertFalse(
            $this->emailService->methodWasCalled("send")
        );

        // make sure the user was still added
        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(1, 1);

        $memberIds = array_map(
            fn(AccountGroupMember $member) => $member->getMemberAccountId(),
            $accountGroupMembers
        );

        $this->assertCount(3, $memberIds);
        $this->assertTrue(in_array(3, $memberIds));
    }

    /**
     * @throws Throwable|ValidationException
     */
    public function testCannotAcceptInvitationForAnotherAccount(): void {

        $pendingAction = new PendingAction(
            "ACCOUNT_GROUP_INVITE",
            1,
            ["accountId" => 3]
        );

        $code = $pendingAction->getIdentifier();

        $this->pendingActionService->returnValue(
            "getPendingActionByIdentifier",
            $pendingAction,
            ["ACCOUNT_GROUP_INVITE", $code]
        );

        $this->expectException(AccessDeniedException::class);

        // Account 1 attempts to accept an invitation intended for account 3.
        $this->accountGroupService->acceptAccountGroupInvitation($code, 1);
    }

    /**
     * @throws ValidationException|AccessDeniedException|Throwable
     */
    public function testCannotAcceptInvalidInvitationCode(): void {

        $this->pendingActionService->throwException(
            "getPendingActionByIdentifier",
            new ItemNotFoundException()
        );

        $this->expectException(ValidationException::class);

        $this->accountGroupService->acceptAccountGroupInvitation("invalid-code", 3);
    }

    /**
     * @throws AccessDeniedException|InvalidAccountGroupOwnerException
     */
    public function testCanListActiveAccountGroupInvitations() {

        $this->pendingActionService->returnValue("getAllPendingActionsForTypeAndObjectId",
            [new PendingAction("ACCOUNT_GROUP_INVITE", 1, ["accountId" => 1])],
            ["ACCOUNT_GROUP_INVITE", 1]);

        $groupInvitations = $this->accountGroupService->getActiveAccountGroupInvitationAccounts(
            1,
            1
        );

        $this->assertTrue(
            $this->pendingActionService->methodWasCalled(
                "getAllPendingActionsForTypeAndObjectId", ["ACCOUNT_GROUP_INVITE", 1]
            )
        );

        $this->assertCount(1, $groupInvitations);

        $this->assertEquals(1, $groupInvitations[0]->getAccountGroupId());
        $this->assertEquals("First Account Group", $groupInvitations[0]->getAccountGroupName());
        $this->assertEquals(1, $groupInvitations[0]->getAccountId());
        $this->assertEquals("Sam Davis Design", $groupInvitations[0]->getAccountName());
        $this->assertEquals("Sam Davis Design", $groupInvitations[0]->getOwningAccountName());
        $this->assertNotNull($groupInvitations[0]->getExpiryDate());
    }

    /**
     * @throws AccessDeniedException|InvalidAccountGroupOwnerException
     */
    public function testListingNoActiveAccountGroupInvitationsReturnsEmptyArray(): void {

        $this->pendingActionService->returnValue("getAllPendingActionsForTypeAndObjectId",
            [],
            ["ACCOUNT_GROUP_INVITE", 1]);

        $groupInvitations = $this->accountGroupService->getActiveAccountGroupInvitationAccounts(1, 1);

        $this->assertTrue(
            $this->pendingActionService->methodWasCalled(
                "getAllPendingActionsForTypeAndObjectId", ["ACCOUNT_GROUP_INVITE", 1]
            )
        );

        $this->assertEmpty($groupInvitations);
    }

    /**
     * @throws InvalidAccountGroupOwnerException
     */
    public function testNonMemberCannotListActiveAccountGroupInvitations() {

        $this->pendingActionService->returnValue("getAllPendingActionsForTypeAndObjectId",
            [new PendingAction("ACCOUNT_GROUP_INVITE", 1, ["accountId" => 1])],
            ["ACCOUNT_GROUP_INVITE", 1]);

        try {
            $this->accountGroupService->getActiveAccountGroupInvitationAccounts(1, 3);
            $this->fail("Expected exception");
        }
        catch(AccessDeniedException) {}

        $this->assertFalse(
            $this->pendingActionService->methodWasCalled(
                "getAllPendingActionsForTypeAndObjectId", ["ACCOUNT_GROUP_INVITE", 1]
            )
        );
    }

    /**
     * @throws AccessDeniedException|InvalidAccountGroupOwnerException
     */
    public function testAccountOwnerCanRevokeAccountGroupInvitation(): void {

        $pendingAction = new PendingAction(
            "ACCOUNT_GROUP_INVITE",
            1,
            ["accountId" => 3]
        );
        $invitationCode = $pendingAction->getIdentifier();

        $this->pendingActionService->returnValue(
            "getAllPendingActionsForTypeAndObjectId",
            [$pendingAction],
            ["ACCOUNT_GROUP_INVITE", 1]
        );

        $invitations = $this->accountGroupService->getActiveAccountGroupInvitationAccounts(1, 1);
        $this->assertCount(1, $invitations);

        $this->accountGroupService->revokeAccountGroupInvitation($invitations[0], 1);

        $this->assertTrue(
            $this->pendingActionService->methodWasCalled(
                "removePendingAction",
                ["ACCOUNT_GROUP_INVITE", $invitationCode]
            )
        );

    }

    /**
     * @throws AccessDeniedException|InvalidAccountGroupOwnerException
     */
    public function testAccountOwnerCanRevokeAccountGroupInvitationWhenMultipleInvitationsExist(): void {

        $pendingAction1 = new PendingAction(
            "ACCOUNT_GROUP_INVITE",
            1,
            ["accountId" => 3]
        );
        $invitationCode1 = $pendingAction1->getIdentifier();

        $pendingAction2 = new PendingAction(
            "ACCOUNT_GROUP_INVITE",
            1,
            ["accountId" => 3]
        );
        $invitationCode2 = $pendingAction2->getIdentifier();

        $this->pendingActionService->returnValue(
            "getAllPendingActionsForTypeAndObjectId",
            [$pendingAction1, $pendingAction2],
            ["ACCOUNT_GROUP_INVITE", 1]
        );

        $invitations = $this->accountGroupService->getActiveAccountGroupInvitationAccounts(1, 1);
        $this->assertCount(2, $invitations);

        $this->accountGroupService->revokeAccountGroupInvitation($invitations[0], 1);

        $this->assertTrue(
            $this->pendingActionService->methodWasCalled(
                "removePendingAction",
                ["ACCOUNT_GROUP_INVITE", $invitationCode1]
            )
        );

        $this->assertTrue(
            $this->pendingActionService->methodWasCalled(
                "removePendingAction",
                ["ACCOUNT_GROUP_INVITE", $invitationCode2]
            )
        );

    }

    /**
     * @throws AccessDeniedException|InvalidAccountGroupOwnerException
     */
    public function testAccountCannotRevokeAccountGroupInvitationIfNotGroupOwner(): void {

        $pendingAction = new PendingAction(
            "ACCOUNT_GROUP_INVITE",
            1,
            ["accountId" => 3]
        );

        $this->pendingActionService->returnValue(
            "getAllPendingActionsForTypeAndObjectId",
            [$pendingAction],
            ["ACCOUNT_GROUP_INVITE", 1]
        );

        $invitations = $this->accountGroupService->getActiveAccountGroupInvitationAccounts(1, 1);
        $this->assertCount(1, $invitations);

        $this->expectException(InvalidAccountGroupOwnerException::class);

        // Account 2 is a member of group 1, but is not its owner.
        $this->accountGroupService->revokeAccountGroupInvitation($invitations[0], 2);
    }

    /**
     * @throws AccessDeniedException|InvalidAccountGroupOwnerException
     */
    public function testAccountCannotRevokeAccountGroupInvitationIfNotGroupMember(): void {

        $pendingAction = new PendingAction(
            "ACCOUNT_GROUP_INVITE",
            1,
            ["accountId" => 3]
        );

        $this->pendingActionService->returnValue(
            "getAllPendingActionsForTypeAndObjectId",
            [$pendingAction],
            ["ACCOUNT_GROUP_INVITE", 1]
        );

        $invitations = $this->accountGroupService->getActiveAccountGroupInvitationAccounts(1, 1);
        $this->assertCount(1, $invitations);

        $this->expectException(AccessDeniedException::class);

        // Account 4 is not a member of group 1
        $this->accountGroupService->revokeAccountGroupInvitation($invitations[0], 4);
    }

    /**
     * @throws AccessDeniedException|ValidationException
     */
    public function testCanGetAccountGroupInvitationDetails() {

        $pendingAction = new PendingAction("ACCOUNT_GROUP_INVITE", 2, ["accountId" => 3]);
        $code = $pendingAction->getIdentifier();

        $this->pendingActionService->returnValue(
            "getPendingActionByIdentifier",
            $pendingAction,
            ["ACCOUNT_GROUP_INVITE", $code]
        );

        $invitationDetails = $this->accountGroupService->getInvitationDetails($code, 3);

        $this->assertEquals(2, $invitationDetails->getAccountGroupId());
        $this->assertEquals(3, $invitationDetails->getAccountId());
    }

    /**
     * @throws ValidationException
     */
    public function testCannotGetInvitationDetailsForAnotherAccount(): void {

        $pendingAction = new PendingAction(
            "ACCOUNT_GROUP_INVITE",
            1,
            ["accountId" => 3]
        );

        $code = $pendingAction->getIdentifier();

        $this->pendingActionService->returnValue(
            "getPendingActionByIdentifier",
            $pendingAction,
            ["ACCOUNT_GROUP_INVITE", $code]
        );

        $this->expectException(AccessDeniedException::class);

        // Account 1 attempts to retrieve an invitation intended for account 3.
        $this->accountGroupService->getInvitationDetails($code, 1);
    }

    /**
     * @throws AccessDeniedException
     */
    public function testGettingInvalidInvitationCodeThrowsValidationException(): void {

        $this->pendingActionService->throwException(
            "getPendingActionByIdentifier",
            new ItemNotFoundException()
        );

        $this->expectException(ValidationException::class);

        $this->accountGroupService->getInvitationDetails("invalid-code", 3);
    }

    public function testCanGetMultipleAccountGroupsById() {

        $this->securityService->becomeAccount(1);

        $accountGroupId1 = $this->accountGroupService->createAccountGroup(
            new AccountGroupDescriptor(
                "Account Group 1",
                "First group",
                "group",
                1
            )
        );

        $accountGroupId2 = $this->accountGroupService->createAccountGroup(
            new AccountGroupDescriptor(
                "Account Group 2",
                "Second group",
                "group",
                1
            )
        );

        $accountGroupId3 = $this->accountGroupService->createAccountGroup(
            new AccountGroupDescriptor(
                "Account Group 3",
                "Third group",
                "group",
                1
            )
        );

        $groups = $this->accountGroupService->getMultipleAccountGroups(
            [$accountGroupId2, $accountGroupId3],
            1
        );

        $actualIds = array_map(
            fn(AccountGroup $group) => $group->getId(),
            array_values($groups)
        );

        sort($actualIds);

        $expectedIds = [$accountGroupId2, $accountGroupId3];
        sort($expectedIds);

        $this->assertEquals($expectedIds, $actualIds);
    }

    public function testGetMultipleAccountGroupsOnlyReturnsGroupsTheAccountBelongsTo(): void {

        $groups = $this->accountGroupService->getMultipleAccountGroups([1, 2], 3);

        $returnedIds = array_map(
            fn(AccountGroup $group) => $group->getId(),
            array_values($groups)
        );

        $this->assertEquals([2], array_values($returnedIds));
    }

    public function testGetMultipleAccountGroupsWithEmptyInputReturnsEmptyArray(): void {

        $groups = $this->accountGroupService->getMultipleAccountGroups([], 1);

        $this->assertEmpty($groups);
    }

}