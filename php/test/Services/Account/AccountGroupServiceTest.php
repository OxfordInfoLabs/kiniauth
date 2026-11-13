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

        $this->securityService = Container::instance()->get(SecurityService::class);
        $this->securityService->logout();
        $this->securityService->becomeSuperUser();

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

        $this->securityService->logout();

        $fileResolver = Container::instance()->get(FileResolver::class);
        $fileResolver->addSearchPath(__DIR__ . "/../../../src");

        $this->emailService = MockObjectProvider::instance()->getMockInstance(EmailService::class);
        $this->pendingActionService = MockObjectProvider::instance()->getMockInstance(
            PendingActionService::class
        );
        $this->accountService = MockObjectProvider::instance()->getMockInstance(AccountService::class);

        $this->securityService = Container::instance()->get(SecurityService::class);

        $this->accountGroupService = new AccountGroupService(
            $this->emailService,
            $this->pendingActionService,
            Container::instance()->get(ActiveRecordInterceptor::class),
            $this->accountService,
            $this->securityService
        );
    }

    public function tearDown(): void {

        // logout of any accounts that might have been logged in
        $this->securityService->logout();

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
        $this->securityService->becomeSuperUser();
        $accountGroups = $this->accountGroupService->getAllAccountGroups();

        $this->assertCount(2, $accountGroups);
        $this->assertEquals("First Account Group", $accountGroups[0]->getName());
        $this->assertEquals("Second Account Group", $accountGroups[1]->getName());

        $this->assertCount(2, $accountGroups[0]->getAccountGroupMembers());
        $this->assertCount(3, $accountGroups[1]->getAccountGroupMembers());
    }

    public function testCanRetrieveAccountGroupAsAMember(): void {

        $this->securityService->becomeAccount(1);

        $accountGroup = $this->accountGroupService->getAccountGroup(1);

        $this->assertEquals(1, $accountGroup->getId());
        $this->assertEquals("First Account Group", $accountGroup->getName());
        $this->assertEquals("description", $accountGroup->getDescription());
        $this->assertEquals(1, $accountGroup->getOwnerAccountId());

        $this->assertCount(2, $accountGroup->getAccountGroupMembers());
    }

    public function testNonMemberCannotGetAccountGroup() {

        // Account 4 is not in any account group
        $this->securityService->becomeAccount(4);

        $this->expectException(ObjectNotFoundException::class);

        $this->accountGroupService->getAccountGroup(1);
    }

    public function testCannotGetAccountGroupThatDoesNotExist() {

        $this->expectException(ObjectNotFoundException::class);

        // group 5 does not exist
        $this->accountGroupService->getAccountGroup(5);
    }

    public function testCanGetMembersOfAccountGroup() {

        $this->securityService->becomeAccount(1);

        $groupOneMembers = $this->accountGroupService->getMembersOfAccountGroup(1);
        $groupTwoMembers = $this->accountGroupService->getMembersOfAccountGroup(2);

        $this->assertCount(2, $groupOneMembers);
        $this->assertCount(3, $groupTwoMembers);
    }

    public function testNonMemberCannotRetrieveAccountGroupMembers(): void {

        // Account 4 is not a member of account group 1
        $this->securityService->becomeAccount(4);

        $this->expectException(ObjectNotFoundException::class);

        $this->accountGroupService->getMembersOfAccountGroup(1);
    }

    public function testCanListAccountGroupsForAccount() {
        $this->securityService->becomeAccount(1);

        $accountGroups = $this->accountGroupService->listAccountGroupsForAccount(1);
        $this->assertCount(2, $accountGroups);

        $this->securityService->becomeAccount(2);
        $accountGroups = $this->accountGroupService->listAccountGroupsForAccount(2);
        $this->assertCount(2, $accountGroups);

        $this->securityService->becomeAccount(3);
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
        $this->accountGroupService->deleteAccountGroup($accountGroupId);

        // make sure to see that the group no longer exists
        try {
            AccountGroup::fetch($accountGroupId);
            $this->fail("Account group object still exist");
        }
        catch (Exception) {}

        // make sure its members were removed as well
        try {
            AccountGroupMember::fetch([$accountGroupId, 1]);
            $this->fail("Account group member object(s) still exist");
        }
        catch (Exception) {}
    }

    public function testNonOwnerCannotDeleteAccountGroup(): void {

        $this->securityService->becomeAccount(2);

        $this->expectException(InvalidAccountGroupOwnerException::class);

        $this->accountGroupService->deleteAccountGroup(1);
    }

    /**
     * @throws InvalidAccountGroupOwnerException
     */
    public function testCanAddMembersToAccountGroup() {

        $this->securityService->becomeAccount(1);
        $this->accountGroupService->addMemberToAccountGroup(1, 4);

        try {
            AccountGroupMember::fetch([1, 4]);
            $this->assertTrue(true);
        } catch (Exception) {
            $this->fail("Couldn't find object");
        }
    }

    /**
     * @throws InvalidAccountGroupOwnerException
     */
    public function testAddingExistingMemberDoesNotCreateDuplicateMembership(): void {

        $this->securityService->becomeAccount(1);

        // user 2 already in account group 1
        $this->accountGroupService->addMemberToAccountGroup(1, 2);

        $members = $this->accountGroupService->getMembersOfAccountGroup(1);

        $memberIds = array_map(
            fn(AccountGroupMember $member) => $member->getMemberAccountId(),
            $members
        );

        $this->assertCount(2, $members);
        $this->assertCount(1, array_filter($memberIds, fn($id) => $id == 2));
    }

    public function testNonOwnerCannotAddMemberToAccountGroup(): void {

        // Account 2 is a member but not the owner of group 1.
        $this->securityService->becomeAccount(2);

        $this->expectException(InvalidAccountGroupOwnerException::class);

        $this->accountGroupService->addMemberToAccountGroup(1, 4);
    }

    /**
     * @throws Exception
     */
    public function testCanRemoveMembersFromAccountGroup() {

        $this->securityService->becomeAccount(2);
        // Remove someone
        $this->accountGroupService->removeMemberFromAccountGroup(2, 3);

        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(2);
        $this->assertCount(2, $accountGroupMembers);

        // Remove someone else
        $this->accountGroupService->removeMemberFromAccountGroup(2, 1);

        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(2);
        $this->assertCount(1, $accountGroupMembers);
    }

    public function testRemovingNonExistentMembersFromAccountGroupDoesNothing(): void {

        $this->securityService->becomeAccount(2);

        try {
            // Remove someone
            $this->accountGroupService->removeMemberFromAccountGroup(2, 4);
        }
        catch (Exception $e) {
            $this->fail("Some exception was thrown: " . $e->getMessage());
        }

        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(2);
        $this->assertCount(3, $accountGroupMembers);
    }

    /**
     * @throws Exception
     */
    public function testNonOwnerCannotRemoveMemberFromAccountGroup(): void {

        // Account 2 is not the owner of group 1
        $this->securityService->becomeAccount(2);

        $this->expectException(InvalidAccountGroupOwnerException::class);

        $this->accountGroupService->removeMemberFromAccountGroup(1, 2);
    }

    /**
     * @throws Exception
     */
    public function testOwnerCannotRemoveThemselvesFromAccountGroup(): void {

        $this->securityService->becomeAccount(1);

        $this->expectException(AccountGroupOwnerCannotRemoveThemselvesException::class);

        $this->accountGroupService->removeMemberFromAccountGroup(1, 1);
    }

    /**
     * @throws AccountGroupOwnerCannotLeaveException
     */
    public function testCanLeaveAccountGroup() {

        $this->securityService->becomeAccount(3);

        // Account 3 leaves from group 2
        $this->accountGroupService->leaveAccountGroup(2);

        $this->securityService->becomeAccount(1);

        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(2);
        $this->assertCount(2, $accountGroupMembers);
    }

    /**
     * @throws AccountGroupOwnerCannotLeaveException
     */
    public function testNonMemberAttemptingToLeaveAccountGroupWillThrowAnException(): void {

        $this->securityService->becomeAccount(4);

        try {
            // non-member tries to leave account group
            $this->accountGroupService->leaveAccountGroup(2);
            $this->fail("Exception should have been thrown");
        }
        catch (ObjectNotFoundException) {
        }

        $this->securityService->becomeAccount(2);

        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(2);
        $this->assertCount(3, $accountGroupMembers);
    }

    public function testOwnerCannotLeaveTheirOwnAccountGroup() {

        // Account 1 is the owner of account group 1
        $this->securityService->becomeAccount(1);

        try {
            $this->accountGroupService->leaveAccountGroup(1);
            $this->fail("Owner shouldn't be able to leave their own group");
        }
        catch (AccountGroupOwnerCannotLeaveException $e) {
            $this->assertEquals("The account group owner cannot leave their own group", $e->getMessage());
        }

        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(1);
        $this->assertCount(2, $accountGroupMembers);
    }

    /**
     * @throws AccountGroupOwnerCannotLeaveException
     */
    public function testLeavingAccountGroupRemovesOnlyTheLeavingAccount(): void {

        $this->securityService->becomeAccount(3);

        $this->accountGroupService->leaveAccountGroup(2);

        $this->securityService->becomeAccount(1);

        $members = $this->accountGroupService->getMembersOfAccountGroup(2);

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

        $this->securityService->becomeAccount(1);

        $this->accountGroupService->inviteAccountToAccountGroup(1, "APPLEPIE");

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
        $this->securityService->becomeAccount(3);

        $activeRecordInterceptor = Container::instance()->get(ActiveRecordInterceptor::class);
        $activeRecordInterceptor->executeInsecure(function () {
            $this->accountGroupService->acceptAccountGroupInvitation("mycode123");
        });


        try {
            AccountGroupMember::fetch([1, 3]);
            $this->assertTrue(true);
        } catch (Exception) {
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

        $this->securityService->becomeAccount(2);

        try {
            $this->accountGroupService->inviteAccountToAccountGroup(1, "APPLEPIE");
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

        $account = new Account("Smart Coasting", 0, AccountSummary::STATUS_ACTIVE, 3);
        $this->accountService->returnValue("getAccountByExternalIdentifier", $account, ["APPLEPIE"]);

        // save user 3 to the account
        $accountGroupMember = new AccountGroupMember(1, 3);
        $accountGroupMember->save();

        $this->securityService->becomeAccount(1);

        try {
            $this->accountGroupService->inviteAccountToAccountGroup(1, "APPLEPIE");
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

        // fake an exception from the email service
        $this->emailService->throwException("send", new Exception());

        $this->securityService->becomeAccount(1);

        try {
            $this->accountGroupService->inviteAccountToAccountGroup(1, "APPLEPIE");
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

        $this->securityService->becomeAccount(3);

        // Account 1 attempts to accept an invitation intended for account 3.
        try {
            $this->accountGroupService->acceptAccountGroupInvitation($code);
            $this->fail("Exception should have thrown here");
        }
        catch (AccountAlreadyAttachedToAccountGroupException $e) {
            $this->assertEquals(
                "The account Smart Coasting is already attached to this account group",
                $e->getMessage()
            );
        }

        $this->assertTrue(
            $this->pendingActionService->methodWasCalled(
                "removePendingAction", ["ACCOUNT_GROUP_INVITE", $code]
            )
        );

        // make sure no duplicate members were made
        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(1);

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

        try {
            $this->accountGroupService->acceptAccountGroupInvitation($code);
            $this->fail("Exception should have thrown here");
        }
        catch (Exception) {}

        $this->securityService->logout();

        $this->assertTrue(
            $this->pendingActionService->methodWasCalled(
                "removePendingAction", ["ACCOUNT_GROUP_INVITE", $code]
            )
        );

        $this->assertFalse($this->emailService->methodWasCalled("send"));

        $this->securityService->becomeAccount(1);

        // make sure the user was still added
        $accountGroupMembers = $this->accountGroupService->getMembersOfAccountGroup(1);

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

        $this->securityService->becomeAccount(1);

        $this->expectException(AccessDeniedException::class);

        // Account 1 attempts to accept an invitation intended for account 3.
        $this->accountGroupService->acceptAccountGroupInvitation($code);
    }

    /**
     * @throws ValidationException|AccessDeniedException|Throwable
     */
    public function testCannotAcceptInvalidInvitationCode(): void {

        $this->pendingActionService->throwException(
            "getPendingActionByIdentifier",
            new ItemNotFoundException()
        );

        $this->securityService->becomeAccount(3);

        $this->expectException(ValidationException::class);

        $this->accountGroupService->acceptAccountGroupInvitation("invalid-code");
    }

    /**
     * @throws AccessDeniedException|InvalidAccountGroupOwnerException
     */
    public function testCanListActiveAccountGroupInvitations() {

        $this->securityService->becomeAccount(1);

        $this->pendingActionService->returnValue("getAllPendingActionsForTypeAndObjectId",
            [new PendingAction("ACCOUNT_GROUP_INVITE", 1, ["accountId" => 1])],
            ["ACCOUNT_GROUP_INVITE", 1]);

        $groupInvitations = $this->accountGroupService->getActiveAccountGroupInvitationAccounts(1);

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

        $this->securityService->becomeAccount(1);

        $this->pendingActionService->returnValue("getAllPendingActionsForTypeAndObjectId",
            [],
            ["ACCOUNT_GROUP_INVITE", 1]
        );

        $groupInvitations = $this->accountGroupService->getActiveAccountGroupInvitationAccounts(1);

        $this->assertTrue(
            $this->pendingActionService->methodWasCalled(
                "getAllPendingActionsForTypeAndObjectId", ["ACCOUNT_GROUP_INVITE", 1]
            )
        );

        $this->assertEmpty($groupInvitations);
    }

    public function testNonMemberCannotListActiveAccountGroupInvitations() {

        $this->securityService->becomeAccount(3);

        $this->pendingActionService->returnValue(
            "getAllPendingActionsForTypeAndObjectId",
            [new PendingAction("ACCOUNT_GROUP_INVITE", 1, ["accountId" => 1])],
            ["ACCOUNT_GROUP_INVITE", 1]
        );

        try {
            $this->accountGroupService->getActiveAccountGroupInvitationAccounts(1);
            $this->fail("Expected exception");
        }
        catch(Exception) {}

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

        $this->securityService->becomeAccount(1);

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

        $invitations = $this->accountGroupService->getActiveAccountGroupInvitationAccounts(1);
        $this->assertCount(1, $invitations);

        $this->accountGroupService->revokeAccountGroupInvitation($invitations[0]);

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

        $this->securityService->becomeAccount(1);

        $invitations = $this->accountGroupService->getActiveAccountGroupInvitationAccounts(1);
        $this->assertCount(2, $invitations);

        $this->accountGroupService->revokeAccountGroupInvitation($invitations[0]);

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

        $this->securityService->becomeAccount(1);

        $invitations = $this->accountGroupService->getActiveAccountGroupInvitationAccounts(1);
        $this->assertCount(1, $invitations);

        $this->securityService->becomeAccount(2);

        $this->expectException(InvalidAccountGroupOwnerException::class);

        // Account 2 is a member of group 1, but is not its owner.
        $this->accountGroupService->revokeAccountGroupInvitation($invitations[0]);
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

        $this->securityService->becomeAccount(1);

        $invitations = $this->accountGroupService->getActiveAccountGroupInvitationAccounts(1);
        $this->assertCount(1, $invitations);

        $this->securityService->becomeAccount(4);

        $this->expectException(ObjectNotFoundException::class);

        // Account 4 is not a member of group 1
        $this->accountGroupService->revokeAccountGroupInvitation($invitations[0]);
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

        $this->securityService->becomeAccount(3);

        $invitationDetails = $this->accountGroupService->getInvitationDetails($code);

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

        $this->securityService->becomeAccount(1);

        $this->expectException(AccessDeniedException::class);

        // Account 1 attempts to retrieve an invitation intended for account 3.
        $this->accountGroupService->getInvitationDetails($code);
    }

    /**
     * @throws AccessDeniedException
     */
    public function testGettingInvalidInvitationCodeThrowsValidationException(): void {

        $this->pendingActionService->throwException(
            "getPendingActionByIdentifier",
            new ItemNotFoundException()
        );

        $this->securityService->becomeAccount(3);

        $this->expectException(ValidationException::class);

        $this->accountGroupService->getInvitationDetails("invalid-code");
    }

    public function testCanGetMultipleAccountGroupsById() {

        $this->securityService->becomeAccount(1);

        $this->accountGroupService->createAccountGroup(
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

        $groups = $this->accountGroupService->getMultipleAccountGroups([$accountGroupId2, $accountGroupId3]);

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

        $this->securityService->becomeAccount(3);

        $groups = $this->accountGroupService->getMultipleAccountGroups([1, 2]);

        $returnedIds = array_map(
            fn(AccountGroup $group) => $group->getId(),
            array_values($groups)
        );

        $this->assertEquals([2], array_values($returnedIds));
    }

    public function testGetMultipleAccountGroupsWithEmptyInputReturnsEmptyArray(): void {

        $this->securityService->becomeAccount(1);

        $groups = $this->accountGroupService->getMultipleAccountGroups([]);

        $this->assertEmpty($groups);
    }

}