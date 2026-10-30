<?php

namespace Kiniauth\Test\Services\Communication\Messaging;


use Exception;
use Kiniauth\Exception\Security\InvalidLoginException;
use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Objects\Communication\Messaging\Message;
use Kiniauth\Objects\Communication\Messaging\MessageReaction;
use Kiniauth\Objects\Communication\Messaging\MessageReactionSummary;
use Kiniauth\Objects\Communication\Messaging\MessageSummary;
use Kiniauth\Objects\Communication\Messaging\MessageThread;
use Kiniauth\Objects\Communication\Messaging\MessageThreadSummary;
use Kiniauth\Objects\Communication\Notification\Notification;
use Kiniauth\Objects\Communication\Notification\UserNotification;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Communication\Messaging\MessagingService;
use Kiniauth\Services\Communication\Notification\CommunicationMethod\EmailNotificationCommunicationMethod;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\Communication\Messaging\MessageType;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

include_once __DIR__ . "/../../../autoloader.php";

class MessagingServiceTest extends TestBase {


    private MessagingService $messagingService;
    private array $users;
    private array $userAccounts;
    private array $userGroups;
    private array $messageThreadIds;
    private UserService $userService;

    /**
     * @throws Exception
     */
    public function setUp(): void {
        parent::setUp();

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $this->messagingService = new MessagingService(
        );

        // create a test group with test users
        $this->userService = Container::instance()->get(UserService::class);

        $userId1 = $this->userService->createUser(
            "hello@myworld.com",
            hash("sha512", "newpassword1"),
            "Hello World"
        );

        $userId2 = $this->userService->createUser(
            "happy@myworld.com",
            hash("sha512", "newpassword1"),
            "Happy World"
        );

        // save the created users for later
        $this->users = [
            User::fetch($userId1),
            User::fetch($userId2)
        ];

        // link the users to an account
        $userRole1 = new UserRole(Role::SCOPE_ACCOUNT, $userId1, 0, 99, $userId1);
        $userRole2 = new UserRole(Role::SCOPE_ACCOUNT, $userId2, 0, 99, $userId2);
        $userRole1->save();
        $userRole2->save();

        $this->userAccounts = [
            $userRole1,
            $userRole2
        ];

        // link the account to a group
        $accountGroupMember = new AccountGroupMember(99, 99);
        $accountGroupMember->save();

        $this->userGroups = [
            $accountGroupMember
        ];

        // create message threads for different contexts
        // user based
        $messageThreadId1 = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                $userId1,
                $userId2,
                null,
                null
            ),
        );

        // user based
        $messageThreadId2 = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                $userId1,
                $userId2,
                null,
                null
            ),
        );

        // account based
        $messageThreadId3 = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                null,
                null,
                99,
                null
            ),
        );

        // group based
        $messageThreadId4 = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                null,
                null,
                null,
                99
            ),
        );

        $this->messageThreadIds = [
            $messageThreadId1,
            $messageThreadId2,
            $messageThreadId3,
            $messageThreadId4,
        ];
    }

    public function tearDown(): void {
        parent::tearDown();

        // clean up any user data created in other tables
        foreach ($this->users as $user) {
            $user->remove();
        }

        foreach ($this->userAccounts as $userAccount) {
            $userAccount->remove();
        }

        foreach ($this->userGroups as $userGroup) {
            $userGroup->remove();
        }

        // clean up all messages, threads and reactions
        $allMessages = Message::filter("WHERE id >= 0");
        $allThreads = MessageThread::filter("WHERE id >= 0");
        $allReactions = MessageReaction::filter("WHERE id >= 0");

        foreach ($allMessages as $message) {
            $message->remove();
        }
        foreach ($allThreads as $thread) {
            $thread->remove();
        }
        foreach ($allReactions as $reaction) {
            $reaction->remove();
        }
    }

    //
    // Saving and Retrieval of messages
    //

    /**
     * @throws Exception
     */
    public function testMessagesCanBeSavedAndRetrievedAtTheUserLevel() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // check that the message was saved to the sender user
        $senderMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $senderMessages);

        $this->assertEquals($this->messageThreadIds[0], $senderMessages[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $senderMessages[0]->getEncryptedMessage());
        $this->assertEquals("general", $senderMessages[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $senderMessages[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $senderMessages[0]->getSenderUserName());
        $this->assertEquals($this->users[0]->getId(), $senderMessages[0]->getReceiverUserId());
        $this->assertNotNull($senderMessages[0]->getMessageDate());

        // check that the message was saved to the receiver user
        $receiverMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $receiverMessages);

        $this->assertEquals($this->messageThreadIds[0], $receiverMessages[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $receiverMessages[0]->getEncryptedMessage());
        $this->assertEquals("general", $receiverMessages[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $receiverMessages[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $receiverMessages[0]->getSenderUserName());
        $this->assertEquals($this->users[1]->getId(), $receiverMessages[0]->getReceiverUserId());
        $this->assertNotNull($receiverMessages[0]->getMessageDate());

        // assert that both messages have the same message ID:
        $this->assertEquals($senderMessages[0]->getMessageId(), $receiverMessages[0]->getMessageId());
    }

    /**
     * @throws Exception
     */
    public function testMessagesCanBeSavedAndRetrievedAtTheAccountLevel() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[2],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: null,
                receiverAccountId: 99,
                receiverGroupId: null,
            )
        );

        // check that the message was saved to all the users of the account
        $senderMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $senderMessages);

        $this->assertEquals($this->messageThreadIds[2], $senderMessages[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $senderMessages[0]->getEncryptedMessage());
        $this->assertEquals("general", $senderMessages[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $senderMessages[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $senderMessages[0]->getSenderUserName());
        $this->assertEquals($this->users[0]->getId(), $senderMessages[0]->getReceiverUserId());
        $this->assertEquals(99, $senderMessages[0]->getReceiverAccountId());
        $this->assertNotNull($senderMessages[0]->getMessageDate());

        // check that the message was saved to the receiver user
        $receiverMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $receiverMessages);

        $this->assertEquals($this->messageThreadIds[2], $receiverMessages[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $receiverMessages[0]->getEncryptedMessage());
        $this->assertEquals("general", $receiverMessages[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $receiverMessages[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $receiverMessages[0]->getSenderUserName());
        $this->assertEquals($this->users[1]->getId(), $receiverMessages[0]->getReceiverUserId());
        $this->assertEquals(99, $receiverMessages[0]->getReceiverAccountId());
        $this->assertNotNull($receiverMessages[0]->getMessageDate());
        $this->assertNotNull($receiverMessages[0]->getMessageDate());

        // assert that both messages have the same message ID:
        $this->assertEquals($senderMessages[0]->getMessageId(), $receiverMessages[0]->getMessageId());
    }

    /**
     * @throws Exception
     */
    public function testMessagesCanBeSavedAndRetrievedAtTheGroupLevel() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[3],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: null,
                receiverAccountId: null,
                receiverGroupId: 99,
            )
        );

        // check that the message was saved to the sender user
        $senderMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $senderMessages);

        $this->assertEquals($this->messageThreadIds[3], $senderMessages[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $senderMessages[0]->getEncryptedMessage());
        $this->assertEquals("general", $senderMessages[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $senderMessages[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $senderMessages[0]->getSenderUserName());
        $this->assertEquals($this->users[0]->getId(), $senderMessages[0]->getReceiverUserId());
        $this->assertEquals(99, $senderMessages[0]->getReceiverGroupId());
        $this->assertNotNull($senderMessages[0]->getMessageDate());

        // check that the message was saved to the receiver user
        $receiverMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $receiverMessages);

        $this->assertEquals($this->messageThreadIds[3], $receiverMessages[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $receiverMessages[0]->getEncryptedMessage());
        $this->assertEquals("general", $receiverMessages[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $receiverMessages[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $receiverMessages[0]->getSenderUserName());
        $this->assertEquals($this->users[1]->getId(), $receiverMessages[0]->getReceiverUserId());
        $this->assertEquals(99, $receiverMessages[0]->getReceiverGroupId());
        $this->assertNotNull($receiverMessages[0]->getMessageDate());

        // assert that both messages have the same message ID:
        $this->assertEquals($senderMessages[0]->getMessageId(), $receiverMessages[0]->getMessageId());
    }

    /**
     * @throws Exception
     */
    public function testMessagesCannotBeSavedAtTheGroupLevelIfSenderIsNotAGroupMember() {

        // create an empty group
        $accountGroupMember = new AccountGroupMember(100, 100);
        $accountGroupMember->save();

        $this->userGroups[] = $accountGroupMember;

        // create a new thread for this group
        $newMessageThreadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                messageThreadGroupId: 100
            )
        );

        try {
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $newMessageThreadId,
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: null,
                    receiverAccountId: null,
                    receiverGroupId: 100,
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("Sender user (ID: {$this->users[0]->getId()}) is not a member of the group (ID: 100)", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$newMessageThreadId]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveMessageWhenSenderIsNotInUserThread() {

        try {
            // user level
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[0],
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: 99,
                    senderUserName: null,
                    senderAccountId: null,
                    receiverUserId: $this->users[1]->getId(),
                    receiverAccountId: null,
                    receiverGroupId: null,
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e){
            $this->assertEquals("Sender user id does not match thread user ids.", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[0]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveMessageWhenRecipientIsNotInUserThread() {

        try {
            // user level
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[0],
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: null,
                    senderAccountId: null,
                    receiverUserId: 100,
                    receiverAccountId: null,
                    receiverGroupId: null,
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("Receiver user id does not match thread user ids.", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[0]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveUserMessageToGroupThread() {

        try {
            // user level
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[3],
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: $this->users[1]->getId(),
                    receiverAccountId: null,
                    receiverGroupId: null,
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("Receiver group id does not match thread group id.", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[3]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveUserMessageToAccountThread() {

        try {
            // user level
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[2],
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: $this->users[1]->getId(),
                    receiverAccountId: null,
                    receiverGroupId: null,
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("Receiver account id does not match thread account id.", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[2]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveMalformedUserMessageToUserThread() {

        try {
            // user level
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[0],
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: $this->users[1]->getId(),
                    receiverAccountId: 99,
                    receiverGroupId: 99,
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals(
                "Incoming user message contains unnecessary account or group data.",
                $e->getMessage()
            );
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[0]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveMessageWhenAccountDoesNotMatchThread() {

        try {
            // account level
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[2],
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: null,
                    receiverAccountId: 101,
                    receiverGroupId: null,
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("Receiver account id does not match thread account id.", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[2]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveAccountMessageToGroupThread() {

        try {
            // account level
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[3],
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: null,
                    receiverAccountId: 99,
                    receiverGroupId: null,
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("Receiver group id does not match thread group id.", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[3]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveAccountMessageToUserThread() {

        try {
            // account level
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[0],
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: null,
                    receiverAccountId: 99,
                    receiverGroupId: null,
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("Receiver user id does not match thread user ids.", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[0]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveMalformedAccountMessageToAccountThread() {

        try {
            // account level
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[2],
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: $this->users[1]->getId(),
                    receiverAccountId: 99,
                    receiverGroupId: 99,
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals(
                "Incoming account message contains unnecessary user or group data.",
                $e->getMessage()
            );
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[2]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveMessageWhenGroupDoesNotMatchThread() {

        try {
            // group level
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[3],
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: null,
                    receiverAccountId: null,
                    receiverGroupId: 101,
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("Receiver group id does not match thread group id.", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[3]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveGroupMessageToUserThread() {

        try {
            // group level
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[0],
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: null,
                    receiverAccountId: null,
                    receiverGroupId: 99,
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("Receiver user id does not match thread user ids.", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[0]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveGroupMessageToAccountThread() {

        try {
            // group level
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[2],
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: null,
                    receiverAccountId: null,
                    receiverGroupId: 99,
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("Receiver account id does not match thread account id.", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[2]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveMalformedGroupMessageToGroupThread() {

        try {
            // group level
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[3],
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: $this->users[1]->getId(),
                    receiverAccountId: 99,
                    receiverGroupId: 99,
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals(
                "Incoming group message contains unnecessary account or user data.",
                $e->getMessage()
            );
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[3]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testEmptyMessageCannotBeSaved() {

        try {
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[0],
                    encryptedMessage: "",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: $this->users[1]->getId(),
                    receiverAccountId: null,
                    receiverGroupId: null,
                )
            );
            $this->fail("Exception should have been thrown");
        }
        catch (Exception $e) {
            $this->assertEquals(
                "New message cannot be empty",
                $e->getMessage()
            );
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[0]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testMessagesCannotBeSavedWithoutAThreadId() {

        try {
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: null,
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: $this->users[1]->getId(),
                    receiverAccountId: null,
                    receiverGroupId: null,
                )
            );
            $this->fail("Exception should have been thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("Message thread id is required to save a message", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE id >= 0"
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testMessagesCannotBeSavedToNonExistingThread() {

        try {
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: 99,
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: $this->users[1]->getId(),
                    receiverAccountId: null,
                    receiverGroupId: null,
                )
            );
            $this->fail("Exception should have been thrown");
        }
        catch (Exception $e) {
            $this->assertEquals(
                "Message thread (ID: 99) cannot be found",
                $e->getMessage()
            );
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [99]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testMessagesCannotBeSavedWithoutAnyRecipientData() {

        try {
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[0],
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                    senderAccountId: null,
                    receiverUserId: null,
                    receiverAccountId: null,
                    receiverGroupId: null,
                )
            );
            $this->fail("Exception should have been thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("At least one recipient parameter must be provided.", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$this->messageThreadIds[0]]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testMessagesCannotBeSavedWithoutAnyActiveRecipientData() {

        // create message thread for this test
        // both users aren't active
        $testMessageThreadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                99,
                100,
                null,
                null
            ),
        );

        try {
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $testMessageThreadId,
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: 99,
                    senderUserName: null,
                    senderAccountId: null,
                    receiverUserId: 100,
                    receiverAccountId: null,
                    receiverGroupId: null,
                )
            );
            $this->fail("Exception should have been thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("No valid users present for saving message", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$testMessageThreadId]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testMessagesCannotBeSavedWhenAUserLacksAPersonalEncryptionKey() {

        // create new test user
        $userId = $this->userService->createUser(
            "bad@myworld.com",
            hash("sha512", "newpassword1"),
            "Bad World"
        );

        // set the personal encryption key to null
        $this->userService->updateUserPersonalEncryptionKey(
            null,
            $userId
        );

        // save the created user, so it will be removed later
        $this->users[] = User::fetch($userId);

        // create message thread for this test
        $testMessageThreadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                messageThreadUserId1: $userId,
                messageThreadUserId2: $userId,
            ),
        );

        try {
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $testMessageThreadId,
                    encryptedMessage: "hello world!",
                    messageType: "general", //MessageType::General,
                    senderUserId: $userId,
                    senderUserName: null,
                    senderAccountId: null,
                    receiverUserId: $userId,
                    receiverAccountId: null,
                    receiverGroupId: null,
                )
            );
            $this->fail("Exception should have been thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("User (ID: {$userId}) does not have a personal encryption key", $e->getMessage());
        }

        // check that no message was saved
        $this->assertCount(0,
            Message::filter(
                "WHERE messageThreadId = ?",
                [$testMessageThreadId]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCanGetAllMessagesFromAGivenThread(): void {

        $messages = [
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            ),
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "hello back!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[1]->getId(),
                senderUserName: $this->users[1]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[0]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            ),
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "this is exciting!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            ),
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "ignore this message!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        ];

        // save all the messages
        $this->messagingService->saveMessage($messages[0]);
        $this->messagingService->saveMessage($messages[1]);
        $this->messagingService->saveMessage($messages[2]);
        $this->messagingService->saveMessage($messages[3]);

        // retrieve all the messages from thread 1
        $messagesGet1User1 = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[0]->getId()
        );
        $messagesGet1User2 = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(3, $messagesGet1User1);
        $this->assertCount(3, $messagesGet1User2);

        // assert the messages arrived in reverse order of when they were saved
        $this->assertEquals("this is exciting!", $messagesGet1User1[0]->getEncryptedMessage());
        $this->assertEquals("this is exciting!", $messagesGet1User2[0]->getEncryptedMessage());

        $this->assertEquals("hello back!", $messagesGet1User1[1]->getEncryptedMessage());
        $this->assertEquals("hello back!", $messagesGet1User2[1]->getEncryptedMessage());

        $this->assertEquals("hello world!", $messagesGet1User1[2]->getEncryptedMessage());
        $this->assertEquals("hello world!", $messagesGet1User2[2]->getEncryptedMessage());


    }

    /**
     * @throws Exception
     */
    public function testCannotGetAllMessagesFromAGivenThreadIfUserDoesNotExist(): void {

        try {
            // retrieve all the messages from thread 1
            $this->messagingService->getAllMessagesAndReactionsFromThread(
                threadId: $this->messageThreadIds[0],
                userId: 99
            );
            $this->fail();
        }
        catch (Exception $e) {
            $this->assertEquals(
                "User (ID: 99) not found in user table.",
                $e->getMessage()
            );
        }

    }

    /**
     * @throws Exception
     */
    public function testCannotGetAllMessagesFromAGivenThreadIfUserLacksAPersonalEncryptionKey(): void {

        // create new test user
        $userId = $this->userService->createUser(
            "bad@myworld.com",
            hash("sha512", "newpassword1"),
            "Bad World"
        );

        // set the personal encryption key to null
        $this->userService->updateUserPersonalEncryptionKey(
            null,
            $userId
        );

        // save the created user, so it will be removed later
        $this->users[] = User::fetch($userId);

        // create message thread for this test
        $testMessageThreadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                messageThreadUserId1: $userId,
                messageThreadUserId2: $userId,
            ),
        );

        // save a new message directly
        $message = new Message(
            new MessageSummary(
                messageThreadId: $testMessageThreadId,
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $userId,
                senderAccountId: null,
                receiverUserId: $userId,
                receiverAccountId: null,
                receiverGroupId: null,
            ),
        );
        $message->save();

        try {
            // retrieve all the messages from the thread
            $this->messagingService->getAllMessagesAndReactionsFromThread(
                threadId: $testMessageThreadId,
                userId: $userId
            );
            $this->fail();
        }
        catch (Exception $e) {
            $this->assertEquals(
                "User (ID: {$userId}) does not have a personal encryption key.",
                $e->getMessage()
            );
        }

    }

    /**
     * @throws Exception
     */
    public function testGettingAllMessagesFromAnEmptyThreadReturnsBlankArray(): void {

        $newMessageThreadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                messageThreadUserId1: $this->users[0]->getId(),
                messageThreadUserId2: $this->users[1]->getId(),
            )
        );

        // retrieve all the messages from thread 1
        $messagesGetUser1 = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $newMessageThreadId,
            userId: $this->users[0]->getId()
        );
        $messagesGetUser2 = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $newMessageThreadId,
            userId: $this->users[1]->getId()
        );

        $this->assertCount(0, $messagesGetUser1);
        $this->assertCount(0, $messagesGetUser2);

    }

    /**
     * @throws Exception
     */
    public function testCanGetLatestMessageFromAThread() {

        $messages = [
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            ),
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "hello back!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[1]->getId(),
                senderUserName: $this->users[1]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[0]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            ),
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "this is exciting!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            ),
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "ignore this message!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        ];

        // save all the messages
        $this->messagingService->saveMessage($messages[0]);
        $this->messagingService->saveMessage($messages[1]);
        $this->messagingService->saveMessage($messages[2]);
        $this->messagingService->saveMessage($messages[3]);

        // retrieve all the messages from threads 1 and 2
        $messagesGet1User1 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[0]->getId()
        );
        $messagesGet1User2 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        );
        $messagesGet2User1 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );
        $messagesGet2User2 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals("this is exciting!", $messagesGet1User1->getEncryptedMessage());
        $this->assertEquals("this is exciting!", $messagesGet1User2->getEncryptedMessage());
        $this->assertEquals("ignore this message!", $messagesGet2User1->getEncryptedMessage());
        $this->assertEquals("ignore this message!", $messagesGet2User2->getEncryptedMessage());
    }

    /**
     * @throws Exception
     */
    public function testCannotGetLatestMessageFromAGivenThreadIfUserDoesNotExist(): void {

        try {
            // retrieve all the messages from thread 1
            $this->messagingService->getLatestMessageFromThread(
                threadId: $this->messageThreadIds[0],
                userId: 99
            );
            $this->fail();
        }
        catch (Exception $e) {
            $this->assertEquals(
                "User (ID: 99) not found in user table.",
                $e->getMessage()
            );
        }

    }

    /**
     * @throws Exception
     */
    public function testCannotGetLatestMessageFromAGivenThreadIfUserLacksAPersonalEncryptionKey(): void {

        // create new test user
        $userId = $this->userService->createUser(
            "bad@myworld.com",
            hash("sha512", "newpassword1"),
            "Bad World"
        );

        // set the personal encryption key to null
        $this->userService->updateUserPersonalEncryptionKey(
            null,
            $userId
        );

        // save the created user, so it will be removed later
        $this->users[] = User::fetch($userId);

        // create message thread for this test
        $testMessageThreadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                messageThreadUserId1: $userId,
                messageThreadUserId2: $userId,
            ),
        );

        // save a new message directly
        $message = new Message(
            new MessageSummary(
                messageThreadId: $testMessageThreadId,
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $userId,
                senderAccountId: null,
                receiverUserId: $userId,
                receiverAccountId: null,
                receiverGroupId: null,
            ),
        );
        $message->save();

        try {
            // retrieve all the messages from the thread
            $this->messagingService->getLatestMessageFromThread(
                threadId: $testMessageThreadId,
                userId: $userId
            );
            $this->fail();
        }
        catch (Exception $e) {
            $this->assertEquals(
                "User (ID: {$userId}) does not have a personal encryption key.",
                $e->getMessage()
            );
        }

    }

    /**
     * @throws Exception
     */
    public function testGettingLatestMessagesFromAnEmptyThreadReturnsNull(): void {

        $newMessageThreadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                messageThreadUserId1: $this->users[0]->getId(),
                messageThreadUserId2: $this->users[1]->getId(),
            )
        );

        // retrieve all the messages from thread 1
        $messagesGetUser1 = $this->messagingService->getLatestMessageFromThread(
            threadId: $newMessageThreadId,
            userId: $this->users[0]->getId()
        );
        $messagesGetUser2 = $this->messagingService->getLatestMessageFromThread(
            threadId: $newMessageThreadId,
            userId: $this->users[1]->getId()
        );

        $this->assertNull($messagesGetUser1);
        $this->assertNull($messagesGetUser2);

    }

    //
    // encryption and decryption
    //

    /**
     * @throws Exception
     */
    public function testMessagesAreStoredEncrypted() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        $lastMessageId = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        )->getMessageId();

        // get the encrypted message for the sender user
        $storedMessage = Message::filter(
            "WHERE messageThreadId = ? AND messageId = ? AND receiverUserId = ?",
            [$this->messageThreadIds[0], $lastMessageId, $this->users[0]->getId()]
        );

        $this->assertCount(1, $storedMessage);
        $this->assertNotEquals("hello world!", $storedMessage[0]->getEncryptedMessage());

        // get the encrypted message for the receiver user
        $storedMessage = Message::filter(
            "WHERE messageThreadId = ? AND messageId = ? AND receiverUserId = ?",
            [$this->messageThreadIds[0], $lastMessageId, $this->users[1]->getId()]
        );

        $this->assertCount(1, $storedMessage);
        $this->assertNotEquals("hello world!", $storedMessage[0]->getEncryptedMessage());
    }

    /**
     * @throws Exception
     */
    public function testUnicodeMessageCanByEncryptedAndDecrypted() {

        $originalMessage = "Hello 👋 — こんにちは — café — مرحباً";

        $this->messagingService->savemessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: $originalMessage,
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        $decryptedMessageUser1 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[0]->getId()
        )->getEncryptedMessage();

        $decryptedMessageUser2 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        )->getEncryptedMessage();

        $this->assertEquals($originalMessage, $decryptedMessageUser1);
        $this->assertEquals($originalMessage, $decryptedMessageUser2);
    }

    /**
     * @throws Exception
     */
    public function testMessageWithMalformedCiphertextComplainsOnRetrieval() {

        $this->messagingService->savemessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        $createdMessage = Message::filter(
            "WHERE messageThreadId = ? AND senderUserId = ? AND receiverUserId = ?",
            [$this->messageThreadIds[0], $this->users[0]->getId(), $this->users[0]->getId()]
        );

        // manually change the ciphertext to be malformed
        $createdMessage[0]->setEncryptedMessage("%%%not-valid-base64%%%");
        $createdMessage[0]->save();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid encrypted message format.");

        // attempt to decrypt the message
        $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[0]->getId()
        )->getEncryptedMessage();

        $this->fail("Exception should have been thrown");
    }

    /**
     * @throws Exception
     */
    public function testMessagesCanOnlyBeDecryptedWithTheCorrectKey() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // get the latest message ID for the message we created
        $lastMessageId = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        )->getMessageId();

        // get the encrypted message for the sender user
        $storedMessage1 = Message::filter(
            "WHERE messageThreadId = ? AND messageId = ? AND receiverUserId = ?",
            [$this->messageThreadIds[0], $lastMessageId, $this->users[0]->getId()]
        );

        $encryptedMessage1 = $storedMessage1[0]->getEncryptedMessage();

        // get the encrypted message for the receiver user
        $storedMessage2 = Message::filter(
            "WHERE messageThreadId = ? AND messageId = ? AND receiverUserId = ?",
            [$this->messageThreadIds[0], $lastMessageId, $this->users[1]->getId()]
        );

        $encryptedMessage2 = $storedMessage2[0]->getEncryptedMessage();

        $applicationSecret = Configuration::readParameter("message.encryption.key");
        $personalEncryptionKey1 = $this->users[0]->getPersonalEncryptionKey();
        $personalEncryptionKey2 = $this->users[1]->getPersonalEncryptionKey();

        $decodedMessage1 = base64_decode($encryptedMessage1, true);
        $decodedMessage2 = base64_decode($encryptedMessage2, true);

        $nonceLength = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;

        $nonce1 = substr($decodedMessage1, 0, $nonceLength);
        $nonce2 = substr($decodedMessage2, 0, $nonceLength);
        $ciphertext1 = substr($decodedMessage1, $nonceLength);
        $ciphertext2 = substr($decodedMessage2, $nonceLength);

        $derivedKey1 = hash_hkdf(
            'sha256',
            $personalEncryptionKey1,
            32,
            'kiniauth-message-encryption',
            $applicationSecret
        );

        $derivedKey2 = hash_hkdf(
            'sha256',
            $personalEncryptionKey2,
            32,
            'kiniauth-message-encryption',
            $applicationSecret
        );

        $decryptedMessage1Sender = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext1,
            '',
            $nonce1,
            $derivedKey1
        );
        $decryptedMessage1Receiver = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext1,
            '',
            $nonce2,
            $derivedKey2
        );

        $decryptedMessage2Sender = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext2,
            '',
            $nonce1,
            $derivedKey1
        );

        $decryptedMessage2Receiver = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext2,
            '',
            $nonce2,
            $derivedKey2
        );

        $this->assertEquals("hello world!", $decryptedMessage1Sender);
        $this->assertNotEquals("hello world!", $decryptedMessage1Receiver);
        $this->assertNotEquals("hello world!", $decryptedMessage2Sender);
        $this->assertEquals("hello world!", $decryptedMessage2Receiver);
    }

    //
    // Message Reactions
    //

    /**
     * @throws Exception
     */
    public function testMessagesAndReactionsCanBeSavedAndRetrievedAtTheUserLevel() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // check that the message was saved to the sender user
        $senderMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $senderMessages);

        $this->assertEquals($this->messageThreadIds[1], $senderMessages[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $senderMessages[0]->getEncryptedMessage());
        $this->assertEquals("general", $senderMessages[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $senderMessages[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $senderMessages[0]->getSenderUserName());
        $this->assertEquals($this->users[0]->getId(), $senderMessages[0]->getReceiverUserId());
        $this->assertNotNull($senderMessages[0]->getMessageDate());

        // check that the message was saved to the receiver user
        $receiverMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $receiverMessages);

        $this->assertEquals($this->messageThreadIds[1], $receiverMessages[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $receiverMessages[0]->getEncryptedMessage());
        $this->assertEquals("general", $receiverMessages[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $receiverMessages[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $receiverMessages[0]->getSenderUserName());
        $this->assertEquals($this->users[1]->getId(), $receiverMessages[0]->getReceiverUserId());
        $this->assertNotNull($receiverMessages[0]->getMessageDate());

        // save a reaction to this message
        $this->messagingService->saveMessageReaction(
            new MessageReactionSummary(
                messageId: $senderMessages[0]->getMessageId(),
                messageThreadId: $senderMessages[0]->getMessageThreadId(),
                reaction: "👍",
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
            )
        );

        // retrieve the message and reaction for the sender user
        $senderMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $senderMessages);
        $this->assertCount(1, $senderMessages[0]->getMessageReactions());
        $this->assertEquals("👍", $senderMessages[0]->getMessageReactions()[0]->getReaction());

        // retrieve the message and reaction for the receiver user
        $receiverMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $receiverMessages);
        $this->assertCount(1, $receiverMessages[0]->getMessageReactions());
        $this->assertEquals("👍", $receiverMessages[0]->getMessageReactions()[0]->getReaction());
    }

    /**
     * @throws Exception
     */
    public function testMessagesAndReactionsCanBeSavedAndRetrievedAtTheAccountLevel() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[2],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: null,
                receiverAccountId: 99,
                receiverGroupId: null,
            )
        );

        // check that the message was saved to the sender user
        $senderMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $senderMessages);

        $this->assertEquals($this->messageThreadIds[2], $senderMessages[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $senderMessages[0]->getEncryptedMessage());
        $this->assertEquals("general", $senderMessages[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $senderMessages[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $senderMessages[0]->getSenderUserName());
        $this->assertEquals($this->users[0]->getId(), $senderMessages[0]->getReceiverUserId());
        $this->assertEquals(99, $senderMessages[0]->getReceiverAccountId());
        $this->assertNotNull($senderMessages[0]->getMessageDate());

        // check that the message was saved to the receiver user
        $receiverMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $receiverMessages);

        $this->assertEquals($this->messageThreadIds[2], $receiverMessages[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $receiverMessages[0]->getEncryptedMessage());
        $this->assertEquals("general", $receiverMessages[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $receiverMessages[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $receiverMessages[0]->getSenderUserName());
        $this->assertEquals($this->users[1]->getId(), $receiverMessages[0]->getReceiverUserId());
        $this->assertEquals(99, $receiverMessages[0]->getReceiverAccountId());
        $this->assertNotNull($receiverMessages[0]->getMessageDate());

        // save a reaction to this message
        $this->messagingService->saveMessageReaction(
            new MessageReactionSummary(
                messageId: $senderMessages[0]->getMessageId(),
                messageThreadId: $senderMessages[0]->getMessageThreadId(),
                reaction: "👍",
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
            )
        );

        // retrieve the message and reaction for the sender user
        $senderMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $senderMessages);
        $this->assertCount(1, $senderMessages[0]->getMessageReactions());
        $this->assertEquals("👍", $senderMessages[0]->getMessageReactions()[0]->getReaction());

        // retrieve the message and reaction for the receiver user
        $receiverMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $receiverMessages);
        $this->assertCount(1, $receiverMessages[0]->getMessageReactions());
        $this->assertEquals("👍", $receiverMessages[0]->getMessageReactions()[0]->getReaction());
    }

    /**
     * @throws Exception
     */
    public function testMessagesAndReactionsCanBeSavedAndRetrievedAtTheGroupLevel() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[3],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: null,
                receiverAccountId: null,
                receiverGroupId: 99,
            )
        );

        // check that the message was saved to the sender user
        $senderMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $senderMessages);

        $this->assertEquals($this->messageThreadIds[3], $senderMessages[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $senderMessages[0]->getEncryptedMessage());
        $this->assertEquals("general", $senderMessages[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $senderMessages[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $senderMessages[0]->getSenderUserName());
        $this->assertEquals($this->users[0]->getId(), $senderMessages[0]->getReceiverUserId());
        $this->assertEquals(99, $senderMessages[0]->getReceiverGroupId());
        $this->assertNotNull($senderMessages[0]->getMessageDate());

        // check that the message was saved to the receiver user
        $receiverMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $receiverMessages);

        $this->assertEquals($this->messageThreadIds[3], $receiverMessages[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $receiverMessages[0]->getEncryptedMessage());
        $this->assertEquals("general", $receiverMessages[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $receiverMessages[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $receiverMessages[0]->getSenderUserName());
        $this->assertEquals($this->users[1]->getId(), $receiverMessages[0]->getReceiverUserId());
        $this->assertEquals(99, $receiverMessages[0]->getReceiverGroupId());
        $this->assertNotNull($receiverMessages[0]->getMessageDate());

        // save a reaction to this message
        $this->messagingService->saveMessageReaction(
            new MessageReactionSummary(
                messageId: $senderMessages[0]->getMessageId(),
                messageThreadId: $senderMessages[0]->getMessageThreadId(),
                reaction: "👍",
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
            )
        );

        // retrieve the message and reaction for the sender user
        $senderMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $senderMessages);
        $this->assertCount(1, $senderMessages[0]->getMessageReactions());
        $this->assertEquals("👍", $senderMessages[0]->getMessageReactions()[0]->getReaction());

        // retrieve the message and reaction for the receiver user
        $receiverMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $receiverMessages);
        $this->assertCount(1, $receiverMessages[0]->getMessageReactions());
        $this->assertEquals("👍", $receiverMessages[0]->getMessageReactions()[0]->getReaction());
    }

    /**
     * @throws Exception
     */
    public function testReactionsCanBeSavedAndDeleted() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // get the message we created
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        // save a reaction to this message
        $this->messagingService->saveMessageReaction(
            new MessageReactionSummary(
                messageId: $senderMessages->getMessageId(),
                messageThreadId: $senderMessages->getMessageThreadId(),
                reaction: "👍",
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
            )
        );

        // retrieve the message and reaction for the sender user
        $senderMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $senderMessages);
        $this->assertCount(1, $senderMessages[0]->getMessageReactions());
        $this->assertEquals("👍", $senderMessages[0]->getMessageReactions()[0]->getReaction());

        // retrieve the message and reaction for the receiver user
        $receiverMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $receiverMessages);
        $this->assertCount(1, $receiverMessages[0]->getMessageReactions());
        $this->assertEquals("👍", $receiverMessages[0]->getMessageReactions()[0]->getReaction());

        // delete the reaction
        $this->messagingService->deleteMessageReaction(
            threadId: $senderMessages[0]->getMessageThreadId(),
            messageId: $senderMessages[0]->getMessageId(),
            userId: $this->users[0]->getId(),
        );

        // retrieve the message and reaction for the sender user
        $senderMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $senderMessages);
        $this->assertEmpty($senderMessages[0]->getMessageReactions());

        // retrieve the message and reaction for the receiver user
        $receiverMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $receiverMessages);
        $this->assertEmpty($receiverMessages[0]->getMessageReactions());
    }

    /**
     * @throws Exception
     */
    public function testSavingAnotherReactionUpdatesExistingReaction() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // get the message we created
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        // save a reaction to this message
        $this->messagingService->saveMessageReaction(
            new MessageReactionSummary(
                messageId: $senderMessages->getMessageId(),
                messageThreadId: $senderMessages->getMessageThreadId(),
                reaction: "👍",
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
            )
        );

        // retrieve the message and reaction for the sender user
        $senderMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $senderMessages);
        $this->assertCount(1, $senderMessages[0]->getMessageReactions());
        $this->assertEquals("👍", $senderMessages[0]->getMessageReactions()[0]->getReaction());

        // retrieve the message and reaction for the receiver user
        $receiverMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $receiverMessages);
        $this->assertCount(1, $receiverMessages[0]->getMessageReactions());
        $this->assertEquals("👍", $receiverMessages[0]->getMessageReactions()[0]->getReaction());

        // save another reaction to this message
        $this->messagingService->saveMessageReaction(
            new MessageReactionSummary(
                messageId: $senderMessages[0]->getMessageId(),
                messageThreadId: $senderMessages[0]->getMessageThreadId(),
                reaction: "❤️",
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
            )
        );

        // retrieve the message and reaction for the sender user
        $senderMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $senderMessages);
        $this->assertCount(1, $senderMessages[0]->getMessageReactions());
        $this->assertEquals("❤️", $senderMessages[0]->getMessageReactions()[0]->getReaction());

        // retrieve the message and reaction for the receiver user
        $receiverMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $receiverMessages);
        $this->assertCount(1, $receiverMessages[0]->getMessageReactions());
        $this->assertEquals("❤️", $receiverMessages[0]->getMessageReactions()[0]->getReaction());
    }

    /**
     * @throws Exception
     */
    public function testReactionsCannotBeSavedForANonExistentMessage() {

        try {
            // save a reaction to a non-existent message
            $this->messagingService->saveMessageReaction(
                new MessageReactionSummary(
                    messageId: 99, // non-existent message id
                    messageThreadId: $this->messageThreadIds[1],
                    reaction: "👍",
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                )
            );
            $this->fail("Expected exception not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("Message (ID: 99) not found in thread (ID: {$this->messageThreadIds[1]})", $e->getMessage());
        }

        // check to see than no message reaction was created
        $messageReactions = MessageReaction::filter(
            "WHERE message_id = 99"
        );
        $this->assertEquals(0, count($messageReactions));
    }

    /**
     * @throws Exception
     */
    public function testEmptyReactionsCannotBeSaved() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // get the message we created
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        try {
            // save a reaction to this message
            $this->messagingService->saveMessageReaction(
                new MessageReactionSummary(
                    messageId: $senderMessages->getMessageId(),
                    messageThreadId: $senderMessages->getMessageThreadId(),
                    reaction: null,
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                )
            );
            $this->fail("Expected exception not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("Reaction cannot be empty", $e->getMessage());
        }

        try {
            // save a reaction to this message
            $this->messagingService->saveMessageReaction(
                new MessageReactionSummary(
                    messageId: $senderMessages->getMessageId(),
                    messageThreadId: $senderMessages->getMessageThreadId(),
                    reaction: "",
                    senderUserId: $this->users[0]->getId(),
                    senderUserName: $this->users[0]->getName(),
                )
            );
            $this->fail("Expected exception not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals("Reaction cannot be empty", $e->getMessage());
        }

        // make sure no message reactions were created
        $messageReactions = MessageReaction::filter(
            "WHERE messageThreadId = ?",
            [$this->messageThreadIds[1]]
        );
        $this->assertCount(0, $messageReactions);
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveReactionsFromNonRecipient() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // get the message we created
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        try {
            // save a reaction to this message
            $this->messagingService->saveMessageReaction(
                new MessageReactionSummary(
                    messageId: $senderMessages->getMessageId(),
                    messageThreadId: $senderMessages->getMessageThreadId(),
                    reaction: "👍",
                    senderUserId: 99,
                )
            );
            $this->fail("Expected exception not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals(
                "User (ID: 99) is not a recipient of message " .
                "(ID: {$senderMessages->getMessageId()}) in thread (ID: {$senderMessages->getMessageThreadId()})",
                $e->getMessage()
            );
        }

        // make sure no message reactions were created
        $messageReactions = MessageReaction::filter(
            "WHERE messageThreadId = ?",
            [$senderMessages->getMessageId()]
        );
        $this->assertCount(0, $messageReactions);
    }

    /**
     * @throws Exception
     */
    public function testCannotDeleteReactionsThatDoNotExist() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // get the message we created
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        try {
            // delete the reaction
            $this->messagingService->deleteMessageReaction(
                threadId: $senderMessages->getMessageThreadId(),
                messageId: $senderMessages->getMessageId(),
                userId: $this->users[0]->getId(),
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals(
                "User (ID: {$this->users[0]->getId()}) has not reacted to message " .
                "(ID: {$senderMessages->getMessageId()}) in thread (ID: {$senderMessages->getMessageThreadId()})",
                $e->getMessage()
            );
        }

        // retrieve the message and reaction for the sender user
        $senderMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $senderMessages);
        $this->assertEmpty($senderMessages[0]->getMessageReactions());

        // retrieve the message and reaction for the receiver user
        $receiverMessages = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $receiverMessages);
        $this->assertEmpty($receiverMessages[0]->getMessageReactions());
    }

    //
    // Editing messages
    //

    /**
     * @throws Exception
     */
    public function testMessagesCanBeEditedAtTheUserLevel() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // check that the message was saved to the sender user
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertEquals($this->messageThreadIds[1], $senderMessages->getMessageThreadId());
        $this->assertEquals("hello world!", $senderMessages->getEncryptedMessage());

        // check that the message was saved to the receiver user
        $receiverMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[1], $receiverMessages->getMessageThreadId());
        $this->assertEquals("hello world!", $receiverMessages->getEncryptedMessage());

        // edit the message to something new
        $this->messagingService->editMessage(
            threadId: $this->messageThreadIds[1],
            messageId: $senderMessages->getMessageId(),
            newMessage: "goodbye world!",
            userId: $this->users[0]->getId()
        );

        // check again to see if the change persists for all recipients
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertEquals($this->messageThreadIds[1], $senderMessages->getMessageThreadId());
        $this->assertEquals("goodbye world!", $senderMessages->getEncryptedMessage());

        $receiverMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[1], $receiverMessages->getMessageThreadId());
        $this->assertEquals("goodbye world!", $receiverMessages->getEncryptedMessage());
    }

    /**
     * @throws Exception
     */
    public function testMessagesCanBeEditedAtTheAccountLevel() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[2],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: null,
                receiverAccountId: 99,
                receiverGroupId: null,
            )
        );

        // check that the message was saved to the sender user
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[0]->getId()
        );

        $this->assertEquals($this->messageThreadIds[2], $senderMessages->getMessageThreadId());
        $this->assertEquals("hello world!", $senderMessages->getEncryptedMessage());

        // check that the message was saved to the receiver user
        $receiverMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[2], $receiverMessages->getMessageThreadId());
        $this->assertEquals("hello world!", $receiverMessages->getEncryptedMessage());

        // edit the message to something new
        $this->messagingService->editMessage(
            threadId: $this->messageThreadIds[2],
            messageId: $senderMessages->getMessageId(),
            newMessage: "goodbye world!",
            userId: $this->users[0]->getId()
        );

        // check again to see if the change persists
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[0]->getId()
        );

        $this->assertEquals($this->messageThreadIds[2], $senderMessages->getMessageThreadId());
        $this->assertEquals("goodbye world!", $senderMessages->getEncryptedMessage());

        $receiverMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[2], $receiverMessages->getMessageThreadId());
        $this->assertEquals("goodbye world!", $receiverMessages->getEncryptedMessage());
    }

    /**
     * @throws Exception
     */
    public function testMessagesCanBeEditedAtTheGroupLevel() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[3],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: null,
                receiverAccountId: null,
                receiverGroupId: 99,
            )
        );

        // check that the message was saved to the sender user
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[0]->getId()
        );

        $this->assertEquals($this->messageThreadIds[3], $senderMessages->getMessageThreadId());
        $this->assertEquals("hello world!", $senderMessages->getEncryptedMessage());

        // check that the message was saved to the receiver user
        $receiverMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[3], $receiverMessages->getMessageThreadId());
        $this->assertEquals("hello world!", $receiverMessages->getEncryptedMessage());

        // edit the message to something new
        $this->messagingService->editMessage(
            threadId: $this->messageThreadIds[3],
            messageId: $senderMessages->getMessageId(),
            newMessage: "goodbye world!",
            userId: $this->users[0]->getId()
        );

        // check again to see if the change persists
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[0]->getId()
        );

        $this->assertEquals($this->messageThreadIds[3], $senderMessages->getMessageThreadId());
        $this->assertEquals("goodbye world!", $senderMessages->getEncryptedMessage());

        $receiverMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[3], $receiverMessages->getMessageThreadId());
        $this->assertEquals("goodbye world!", $receiverMessages->getEncryptedMessage());
    }

    /**
     * @throws Exception
     */
    public function testMessagesCannotBeEditedWithEmptyMessage() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // check that the message was saved to the sender user
        $messageId = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        )->getMessageId();

        try {
            // edit the message to something new
            $this->messagingService->editMessage(
                threadId: $this->messageThreadIds[1],
                messageId: $messageId,
                newMessage: "",
                userId: $this->users[0]->getId()
            );
            $this->fail("Exception not thrown when editing message");
        }
        catch (Exception $e) {
            $this->assertEquals("New message cannot be empty", $e->getMessage());
        }

        // check to see that the message was unedited
        $senderMessage = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        )->getEncryptedMessage();

        $receiverMessage = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        )->getEncryptedMessage();

        $this->assertEquals("hello world!", $senderMessage);
        $this->assertEquals("hello world!", $receiverMessage);
    }

    /**
     * @throws Exception
     */
    public function testMessagesCannotBeEditedByNonSender() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // check that the message was saved to the sender user
        $messageId = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        )->getMessageId();

        try {
            // edit the message to something new
            // using the receiver's user id
            $this->messagingService->editMessage(
                threadId: $this->messageThreadIds[1],
                messageId: $messageId,
                newMessage: "goodbye world!",
                userId: $this->users[1]->getId()
            );
            $this->fail("Exception not thrown when editing message");
        }
        catch (Exception $e) {
            $this->assertEquals("User (ID: {$this->users[1]->getId()}) is not the sender of the message (ID: {$messageId})."
                . " Only the sender can edit a message.", $e->getMessage());
        }

        // check to see that the message was unedited
        $senderMessage = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        )->getEncryptedMessage();

        $receiverMessage = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        )->getEncryptedMessage();

        $this->assertEquals("hello world!", $senderMessage);
        $this->assertEquals("hello world!", $receiverMessage);
    }

    /**
     * @throws Exception
     */
    public function testNonExistentMessagesCannotBeEdited() {

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Message (ID: 99) not found in thread (ID: {$this->messageThreadIds[1]}).");

        // edit the message to something new
        // using the receiver's user id
        $this->messagingService->editMessage(
            threadId: $this->messageThreadIds[1],
            messageId: 99,
            newMessage: "goodbye world!",
            userId: $this->users[1]->getId()
        );
        $this->fail("Exception not thrown when editing message");
    }

    /**
     * @throws Exception
     */
    public function testMessagesCannotBeEditedIfRecipientIsNotActive() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // get the message id of the message sent by the sender
        $senderMessageId = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        )->getMessageId();

        // remove one of the users
        $this->users[1]->remove();

        try {
            // edit the message to something new
            $this->messagingService->editMessage(
                threadId: $this->messageThreadIds[1],
                messageId: $senderMessageId,
                newMessage: "goodbye world!",
                userId: $this->users[0]->getId()
            );
            $this->fail("Expected exception not thrown ");
        }
        catch (Exception $e) {
            $this->assertEquals(
                "User (ID: {$this->users[1]->getId()}) not found.",
                $e->getMessage()
            );
        }

        // reset the user
        $this->users[1]->save();

        // check to see if the message was unchanged for all recipients
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertEquals($this->messageThreadIds[1], $senderMessages->getMessageThreadId());
        $this->assertEquals("hello world!", $senderMessages->getEncryptedMessage());

        $receiverMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[1], $receiverMessages->getMessageThreadId());
        $this->assertEquals("hello world!", $receiverMessages->getEncryptedMessage());
    }

    /**
     * @throws Exception
     */
    public function testMessagesCannotBeEditedIfRecipientLacksAPersonalEncryptionKey() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // get the message id of the message sent by the sender
        $senderMessageId = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        )->getMessageId();

        // set the receiver users encryption key to null
        $encryptionKey = $this->users[1]->getPersonalEncryptionKey();
        $this->users[1]->setPersonalEncryptionKey(null);
        $this->users[1]->save();

        try {
            // edit the message to something new
            $this->messagingService->editMessage(
                threadId: $this->messageThreadIds[1],
                messageId: $senderMessageId,
                newMessage: "goodbye world!",
                userId: $this->users[0]->getId()
            );
            $this->fail("Expected exception not thrown ");
        }
        catch (Exception $e) {
            $this->assertEquals(
                "User (ID: {$this->users[1]->getId()}) does not have a personal encryption key",
                $e->getMessage()
            );
        }

        // reset the encryption key
        $this->users[1]->setPersonalEncryptionKey($encryptionKey);
        $this->users[1]->save();

        // check to see if the message was unchanged for all recipients
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertEquals($this->messageThreadIds[1], $senderMessages->getMessageThreadId());
        $this->assertEquals("hello world!", $senderMessages->getEncryptedMessage());

        $receiverMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[1], $receiverMessages->getMessageThreadId());
        $this->assertEquals("hello world!", $receiverMessages->getEncryptedMessage());
    }

    //
    // Message Read/Unread
    //

    /**
     * @throws Exception
     */
    public function testMessagesCanBeMarkedReadAtTheUserLevel() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // check that the message was saved to the sender user
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[0]->getId()
        );


        $this->assertTrue($senderMessages->getMessageRead());

        // check that the message was saved to the receiver user
        $receiverMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        );

        $this->assertFalse($receiverMessages->getMessageRead());

        // mark the message as read for the receiver user
        $this->messagingService->markMessageRead(
            threadId: $this->messageThreadIds[0],
            messageId: $receiverMessages->getMessageId(),
            userId: $this->users[1]->getId()
        );

        // check that this has updated for the receiver user
        $receiverMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        );

        $this->assertTrue($receiverMessages->getMessageRead());
    }

    /**
     * @throws Exception
     */
    public function testMessagesCanBeMarkedReadAtTheAccountLevel() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[2],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: null,
                receiverAccountId: 99,
                receiverGroupId: null,
            )
        );

        // check that the message was saved to the sender user
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[0]->getId()
        );


        $this->assertTrue($senderMessages->getMessageRead());

        // check that the message was saved to the receiver user
        $receiverMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[1]->getId()
        );

        $this->assertFalse($receiverMessages->getMessageRead());

        // mark the message as read for the receiver user
        $this->messagingService->markMessageRead(
            threadId: $this->messageThreadIds[2],
            messageId: $receiverMessages->getMessageId(),
            userId: $this->users[1]->getId()
        );

        // check that this has updated for the receiver user
        $receiverMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[1]->getId()
        );

        $this->assertTrue($receiverMessages->getMessageRead());
    }

    /**
     * @throws Exception
     */
    public function testMessagesCanBeMarkedReadAtTheGroupLevel() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[3],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: null,
                receiverAccountId: null,
                receiverGroupId: 99,
            )
        );

        // check that the message was saved to the sender user
        $senderMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[0]->getId()
        );

        $this->assertTrue($senderMessages->getMessageRead());

        // check that the message was saved to the receiver user
        $receiverMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertFalse($receiverMessages->getMessageRead());

        // mark the message as read for the receiver user
        $this->messagingService->markMessageRead(
            threadId: $this->messageThreadIds[3],
            messageId: $receiverMessages->getMessageId(),
            userId: $this->users[1]->getId()
        );

        // check that this has updated for the receiver user
        $receiverMessages = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertTrue($receiverMessages->getMessageRead());
    }

    /**
     * @throws Exception
     */
    public function testCannotMarkNonExistentMessageRead() {

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Message (ID: 99) not found for user (ID: {$this->users[1]->getId()}) ".
            "in thread (ID: {$this->messageThreadIds[0]}).");

        // mark the message as read for the receiver user
        $this->messagingService->markMessageRead(
            threadId: $this->messageThreadIds[0],
            messageId: 99,
            userId: $this->users[1]->getId()
        );
    }

    //
    // Message deletion
    //

    /**
     * @throws Exception
     */
    public function testCanDeleteAMessageFromTheMessageTableForAUser() {

        // create a new message
        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        $messageRow = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[0]->getId()
        );

        // delete the message we just created
        $this->messagingService->deleteMessageForUser(
            threadId: $this->messageThreadIds[0],
            messageId: $messageRow->getMessageId(),
            userId: $this->users[0]->getId()
        );

        // check to see that the message was deleted for the first user
        $removedMessageUser1 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[0]->getId()
        );
        $this->assertNull($removedMessageUser1->getEncryptedMessage());

        // check to see that the message was not deleted for the second user
        $removedMessageUser2 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals("hello world!", $removedMessageUser2->getEncryptedMessage());
    }

    /**
     * @throws Exception
     */
    public function testCannotDeleteANonExistentMessageFromTheMessageTableForAUser() {

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Message (ID: 99) not found in thread (ID: {$this->messageThreadIds[0]})" .
            " for user (ID: {$this->users[0]->getId()})");

        // delete a non-existent message
        $this->messagingService->deleteMessageForUser(
            threadId: $this->messageThreadIds[0],
            messageId: 99,
            userId: $this->users[0]->getId()
        );
    }

    /**
     * @throws Exception
     */
    public function testCanDeleteAMessageFromTheMessageTableForAllUsers() {

        // create a new message
        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        $messageRow = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[0]->getId()
        );

        // delete the message we just created
        $this->messagingService->deleteMessageForAllUsers(
            threadId: $this->messageThreadIds[0],
            messageId: $messageRow->getMessageId(),
            userId: $this->users[0]->getId()
        );

        // check to see that the message was deleted for the first user
        $removedMessageUser1 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[0]->getId()
        );
        $this->assertNull($removedMessageUser1->getEncryptedMessage());

        // check to see that the message was deleted for the second user
        $removedMessageUser2 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        );
        $this->assertNull($removedMessageUser2->getEncryptedMessage());
    }

    /**
     * @throws Exception
     */
    public function testCannotDeleteANonExistentMessageFromTheMessageTableForAllUsers() {

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Message (ID: 99) not found in thread (ID: {$this->messageThreadIds[0]})");

        // delete the non-existent message
        $this->messagingService->deleteMessageForAllUsers(
            threadId: $this->messageThreadIds[0],
            messageId: 99,
            userId: $this->users[0]->getId()
        );
    }

    /**
     * @throws Exception
     */
    public function testCannotDeleteAMessageFromTheMessageTableForAllUsersByANonSenderUser() {

        // create a new message
        $this->messagingService->saveMessage(
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "hello world!",
                messageType: "general", //MessageType::General,
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
                senderAccountId: null,
                receiverUserId: $this->users[1]->getId(),
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        $messageRow = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[0]->getId()
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Only the sender of the message can delete the message for everyone");

        // try to delete the message by the receiver user
        $this->messagingService->deleteMessageForAllUsers(
            threadId: $this->messageThreadIds[0],
            messageId: $messageRow->getMessageId(),
            userId: $this->users[1]->getId()
        );
    }

    //
    // Saving and retrieving message threads
    //

    /**
     * @throws Exception
     */
    public function testCanCreateNewMessageThread() {

        $threadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                messageThreadUserId1: $this->users[0]->getId(),
                messageThreadUserId2: $this->users[1]->getId(),
                messageThreadAccountId: null,
                messageThreadGroupId: null
            )
        );

        // get the message thread summary we just created
        $messageThreadGet = $this->messagingService->getMessageThreadSummaryById($threadId);

        // check it looks correct
        $this->assertEquals($this->users[0]->getId(), $messageThreadGet->getMessageThreadUserId1());
        $this->assertEquals($this->users[1]->getId(), $messageThreadGet->getMessageThreadUserId2());
        $this->assertEquals(null, $messageThreadGet->getMessageThreadAccountId());
        $this->assertEquals(null, $messageThreadGet->getMessageThreadGroupId());
    }

    public function testCannotSaveInvalidMessageThreadWithNoTargets() {

        try {
            $this->messagingService->saveMessageThread(
                new MessageThreadSummary(
                    messageThreadUserId1: null,
                    messageThreadUserId2: null,
                    messageThreadAccountId: null,
                    messageThreadGroupId: null
                )
            );
            $this->fail("Expected exception was not thrown");
        }
        catch (Exception $e) {
            $this->assertEquals(
                "Message thread must be either user-2-user, account based, or group based.",
                $e->getMessage()
            );
        }

        // look to see that the message thread wasn't created
        $messageThreads = MessageThread::filter(
            "WHERE messageThreadUserId1 IS NULL AND ".
            " messageThreadUserId2 IS NULL AND " .
            " messageThreadAccountId IS NULL AND ".
            " messageThreadGroupId IS NULL"
        );

        $this->assertCount(0, $messageThreads);
    }

    public function testCannotSaveInvalidMessageThreadWithOnlyOneUser() {

        try {
            $this->messagingService->saveMessageThread(
                new MessageThreadSummary(
                    messageThreadUserId1: 1,
                    messageThreadUserId2: null,
                    messageThreadAccountId: null,
                    messageThreadGroupId: null
                )
            );
        }
        catch (Exception $e) {
            $this->assertEquals(
                "Message thread must be either user-2-user, account based, or group based.",
                $e->getMessage()
            );
        }

        // look to see that the message thread wasn't created
        $messageThreads = MessageThread::filter(
            "WHERE messageThreadUserId1 = 1 AND ".
            " messageThreadUserId2 IS NULL AND " .
            " messageThreadAccountId IS NULL AND ".
            " messageThreadGroupId IS NULL"
        );

        $this->assertCount(0, $messageThreads);
    }

    public function testCannotSaveInvalidMessageThreadWithMixtureOfTargets() {

        // account and users
        try {
            $this->messagingService->saveMessageThread(
                new MessageThreadSummary(
                    messageThreadUserId1: 1,
                    messageThreadUserId2: 2,
                    messageThreadAccountId: 10,
                    messageThreadGroupId: null
                )
            );
        } catch (Exception $e) {
            $this->assertEquals(
                "Message thread must be either user-2-user, account based, or group based.",
                $e->getMessage()
            );
        }

        // look to see that the message thread wasn't created
        $messageThreads = MessageThread::filter(
            "WHERE messageThreadUserId1 = 1 AND ".
            " messageThreadUserId2 = 2 AND " .
            " messageThreadAccountId = 10 AND ".
            " messageThreadGroupId IS NULL"
        );

        $this->assertCount(0, $messageThreads);

        // group and users
        try {
            $this->messagingService->saveMessageThread(
                new MessageThreadSummary(
                    messageThreadUserId1: 1,
                    messageThreadUserId2: 2,
                    messageThreadAccountId: null,
                    messageThreadGroupId: 20
                )
            );
        } catch (Exception $e) {
            $this->assertEquals(
                "Message thread must be either user-2-user, account based, or group based.",
                $e->getMessage()
            );
        }

        // look to see that the message thread wasn't created
        $messageThreads = MessageThread::filter(
            "WHERE messageThreadUserId1 = 1 AND ".
            " messageThreadUserId2 = 2 AND " .
            " messageThreadAccountId IS NULL AND ".
            " messageThreadGroupId = 20"
        );

        $this->assertCount(0, $messageThreads);

        // group and account
        try {
            $this->messagingService->saveMessageThread(
                new MessageThreadSummary(
                    messageThreadUserId1: null,
                    messageThreadUserId2: null,
                    messageThreadAccountId: 10,
                    messageThreadGroupId: 20
                )
            );
        } catch (Exception $e) {
            $this->assertEquals(
                "Message thread must be either user-2-user, account based, or group based.",
                $e->getMessage()
            );
        }

        // look to see that the message thread wasn't created
        $messageThreads = MessageThread::filter(
            "WHERE messageThreadUserId1 IS NULL AND ".
            " messageThreadUserId2 IS NULL AND " .
            " messageThreadAccountId = 10 AND ".
            " messageThreadGroupId = 20"
        );

        $this->assertCount(0, $messageThreads);
    }

    public function testCannotSaveInvalidMessageThreadWithAllTargets() {

        try {
            $this->messagingService->saveMessageThread(
                new MessageThreadSummary(
                    messageThreadUserId1: 1,
                    messageThreadUserId2: 2,
                    messageThreadAccountId: 10,
                    messageThreadGroupId: 20
                )
            );
        }
        catch (Exception $e) {
            $this->assertEquals(
                "Message thread must be either user-2-user, account based, or group based.",
                $e->getMessage()
            );
        }

        // look to see that the message thread wasn't created
        $messageThreads = MessageThread::filter(
            "WHERE messageThreadUserId1 = 1 AND ".
            " messageThreadUserId2 = 2 AND " .
            " messageThreadAccountId = 10 AND ".
            " messageThreadGroupId = 20"
        );

        $this->assertCount(0, $messageThreads);
    }

    /**
     * @throws Exception
     */
    public function testCanGetAllMessageThreadsAtTheUserLevel() {

        // user level
        $messageThreadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                messageThreadUserId1: 99,
                messageThreadUserId2: 100,
                messageThreadAccountId: null,
                messageThreadGroupId: null
            )
        );

        // get all the threads at the user level
        $userThreadsUser1 = $this->messagingService->getAllMessageThreadsByUserId(99);
        $userThreadsUser2 = $this->messagingService->getAllMessageThreadsByUserId(100);
        $this->assertCount(1, $userThreadsUser1);
        $this->assertCount(1, $userThreadsUser2);
        $this->assertEquals($messageThreadId, $userThreadsUser1[0]->getId());
        $this->assertEquals($messageThreadId, $userThreadsUser2[0]->getId());
    }

    /**
     * @throws Exception
     */
    public function testCanGetAllMessageThreadsAtTheAccountLevel() {

        // account level
        $messageThreadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                messageThreadUserId1: null,
                messageThreadUserId2: null,
                messageThreadAccountId: 100,
                messageThreadGroupId: null
            )
        );

        // get the account level thread
        $accountThreads = $this->messagingService->getAllMessageThreadsByAccountId(100);
        $this->assertCount(1, $accountThreads);
        $this->assertEquals($messageThreadId, $accountThreads[0]->getId());
    }

    /**
     * @throws Exception
     */
    public function testCanGetAllMessageThreadsAtTheGroupLevel() {

        $messageThreadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                messageThreadUserId1: null,
                messageThreadUserId2: null,
                messageThreadAccountId: null,
                messageThreadGroupId: 100
            )
        );

        // get the group level thread
        $groupThreads = $this->messagingService->getAllMessageThreadsByGroupId(100);
        $this->assertCount(1, $groupThreads);
        $this->assertEquals($messageThreadId, $groupThreads[0]->getId());
    }


    //
    // Deleting message threads
    //

    /**
     * @throws Exception
     */
    public function testCanDeleteAMessageThread() {
        $threadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                messageThreadUserId1: $this->users[0]->getId(),
                messageThreadUserId2: $this->users[1]->getId(),
                messageThreadAccountId: null,
                messageThreadGroupId: null
            )
        );

        // delete the thread
        $this->messagingService->deleteMessageThread($threadId);

        $this->expectException(ObjectNotFoundException::class);
        $this->messagingService->getMessageThreadSummaryById($threadId);

    }

    public function testCannotDeleteANonExistentMessageThread() {
        $this->expectException(ObjectNotFoundException::class);

        // delete the thread
        $this->messagingService->deleteMessageThread(99);
    }

}
