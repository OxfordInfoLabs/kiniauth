<?php

namespace Kiniauth\Test\Services\Communication\Messaging;


use Exception;
use Kiniauth\Exception\Security\InvalidLoginException;
use Kiniauth\Objects\Account\AccountGroupMember;
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
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

include_once __DIR__ . "/../../../autoloader.php";

class MessagingServiceTest extends TestBase {

    /**
     * @var MessagingService
     */
    private $messagingService;
    private array $users;
    private array $messageThreadIds;
    private NotificationService $notificationService;
    private EmailNotificationCommunicationMethod $communicationMethod;

    /**
     * @throws InvalidLoginException
     */
    public function setUp(): void {
        parent::setUpBeforeClass();

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $this->notificationService = Container::instance()->get(NotificationService::class);
        $this->communicationMethod = $this->createMock(EmailNotificationCommunicationMethod::class);

        $this->messagingService = new MessagingService(
            $this->notificationService,
            $this->communicationMethod
        );

        // create a test group with test users
        $userService = Container::instance()->get(UserService::class);

        $userId1 = $userService->createUser(
            "hello@myworld.com",
            hash("sha512", "newpassword1"),
            "Hello World"
        );

        $userService->updateUserPersonalEncryptionKey(
            "test-personal-encryption-key",
            $userId1
        );

        $userId2 = $userService->createUser(
            "happy@myworld.com",
            hash("sha512", "newpassword1"),
            "Happy World"
        );

        $userService->updateUserPersonalEncryptionKey(
            "another-test-personal-encryption-key",
            $userId2
        );

        // save the created user Ids for later
        $this->users = [
            User::fetch($userId1),
            User::fetch($userId2)
        ];

        // link the users to an account
        $userRole1 = new UserRole(Role::SCOPE_ACCOUNT, $userId1, 0, 99, $userId1);
        $userRole2 = new UserRole(Role::SCOPE_ACCOUNT, $userId2, 0, 99, $userId2);
        $userRole1->save();
        $userRole2->save();

        // link the account to a group
        $accountGroupMember = new AccountGroupMember(99, 99);
        $accountGroupMember->save();

        // create message threads for different contexts
        $messageThreadId1 = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                $userId1,
                $userId2,
                null,
                null
            ),
        );

        $messageThreadId2 = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                $userId1,
                $userId2,
                null,
                null
            ),
        );

        $messageThreadId3 = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                null,
                null,
                99,
                null
            ),
        );

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
        parent::tearDownAfterClass();

        // clean up any user data created in other tables
        foreach ($this->users as $user) {
            $user->remove();

            $userNotifications = UserNotification::filter(
                "WHERE user_id = ?",
                [$user->getId()]
            );

            foreach ($userNotifications as $userNotification) {
                $userNotification->remove();
            }

            $notifications = Notification::filter(
                "WHERE user_id = ?",
                [$user->getId()]
            );

            foreach ($notifications as $notification) {
                $notification->remove();
            }
        }

        $accountGroupMember = AccountGroupMember::filter("WHERE account_group_id = 99");
        $accountGroupMember[0]->remove();
    }

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
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThreadAndReactions(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[0], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $messageGet[0]->getSenderUserName());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getReceiverUserId());
        $this->assertNotNull($messageGet[0]->getMessageDate());

        // check that the message was saved to the receiver user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThreadAndReactions(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[0], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $messageGet[0]->getSenderUserName());
        $this->assertEquals($this->users[1]->getId(), $messageGet[0]->getReceiverUserId());
        $this->assertNotNull($messageGet[0]->getMessageDate());
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
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThreadAndReactions(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[2], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $messageGet[0]->getSenderUserName());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getReceiverUserId());
        $this->assertEquals(99, $messageGet[0]->getReceiverAccountId());
        $this->assertNotNull($messageGet[0]->getMessageDate());

        // check that the message was saved to the receiver user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThreadAndReactions(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[2], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $messageGet[0]->getSenderUserName());
        $this->assertEquals($this->users[1]->getId(), $messageGet[0]->getReceiverUserId());
        $this->assertEquals(99, $messageGet[0]->getReceiverAccountId());
        $this->assertNotNull($messageGet[0]->getMessageDate());
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
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThreadAndReactions(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[3], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $messageGet[0]->getSenderUserName());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getReceiverUserId());
        $this->assertEquals(99, $messageGet[0]->getReceiverGroupId());
        $this->assertNotNull($messageGet[0]->getMessageDate());

        // check that the message was saved to the receiver user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThreadAndReactions(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[3], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $messageGet[0]->getSenderUserName());
        $this->assertEquals($this->users[1]->getId(), $messageGet[0]->getReceiverUserId());
        $this->assertEquals(99, $messageGet[0]->getReceiverGroupId());
        $this->assertNotNull($messageGet[0]->getMessageDate());
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
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThreadAndReactions(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[3], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $messageGet[0]->getSenderUserName());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getReceiverUserId());
        $this->assertEquals(99, $messageGet[0]->getReceiverGroupId());
        $this->assertNotNull($messageGet[0]->getMessageDate());

        // check that the message was saved to the receiver user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThreadAndReactions(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[3], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $messageGet[0]->getSenderUserName());
        $this->assertEquals($this->users[1]->getId(), $messageGet[0]->getReceiverUserId());
        $this->assertEquals(99, $messageGet[0]->getReceiverGroupId());
        $this->assertNotNull($messageGet[0]->getMessageDate());

        // save a reaction to this message
        $this->messagingService->saveMessageReaction(
            new MessageReactionSummary(
                messageId: $messageGet[0]->getMessageId(),
                messageThreadId: $messageGet[0]->getMessageThreadId(),
                reaction: "👍",
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
            )
        );

        // retrieve the message and reaction for the sender user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThreadAndReactions(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $messageGet);
        $this->assertCount(1, $messageGet[0]->getMessageReactions());

        $this->assertEquals("👍", $messageGet[0]->getMessageReactions()[0]->getReaction());

        // retrieve the message and reaction for the receiver user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThreadAndReactions(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $messageGet);
        $this->assertCount(1, $messageGet[0]->getMessageReactions());

        $this->assertEquals("👍", $messageGet[0]->getMessageReactions()[0]->getReaction());
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
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[0]->getId()
        );

        $this->assertEquals($this->messageThreadIds[3], $messageGet->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet->getEncryptedMessage());
        $this->assertEquals($this->users[0]->getId(), $messageGet->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $messageGet->getSenderUserName());
        $this->assertEquals($this->users[0]->getId(), $messageGet->getReceiverUserId());
        $this->assertEquals(99, $messageGet->getReceiverGroupId());
        $this->assertNotNull($messageGet->getMessageDate());

        // check that the message was saved to the receiver user
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[3], $messageGet->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet->getEncryptedMessage());
        $this->assertEquals($this->users[0]->getId(), $messageGet->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $messageGet->getSenderUserName());
        $this->assertEquals($this->users[1]->getId(), $messageGet->getReceiverUserId());
        $this->assertEquals(99, $messageGet->getReceiverGroupId());
        $this->assertNotNull($messageGet->getMessageDate());

        // edit the message to something new
        $this->messagingService->editMessage(
            threadId: $this->messageThreadIds[3],
            messageId: $messageGet->getMessageId(),
            newMessage: "goodbye world!",
            userId: $this->users[0]->getId()
        );

        // check again to see if the change persists
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[0]->getId()
        );

        $this->assertEquals($this->messageThreadIds[3], $messageGet->getMessageThreadId());
        $this->assertEquals("goodbye world!", $messageGet->getEncryptedMessage());
        $this->assertEquals($this->users[0]->getId(), $messageGet->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $messageGet->getSenderUserName());
        $this->assertEquals($this->users[0]->getId(), $messageGet->getReceiverUserId());
        $this->assertEquals(99, $messageGet->getReceiverGroupId());
        $this->assertNotNull($messageGet->getMessageDate());

        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[3], $messageGet->getMessageThreadId());
        $this->assertEquals("goodbye world!", $messageGet->getEncryptedMessage());
        $this->assertEquals($this->users[0]->getId(), $messageGet->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $messageGet->getSenderUserName());
        $this->assertEquals($this->users[1]->getId(), $messageGet->getReceiverUserId());
        $this->assertEquals(99, $messageGet->getReceiverGroupId());
        $this->assertNotNull($messageGet->getMessageDate());
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveMessageWithMismatchedIdParameters() {

        //ToDo: setup test account

        // user level
        try {
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: $this->messageThreadIds[0],
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

            $this->fail("Expected exception was not thrown");
        } catch (Exception $e) {
            $this->assertTrue(true);
            //ToDo: verify exception message
        }

        // account level
        try {
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
        } catch (Exception $e) {
            $this->assertTrue(true);
            //ToDo: verify exception message
        }

        // group level
        try {
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
        } catch (Exception $e) {
            $this->assertTrue(true);
            //ToDo: verify exception message
        }
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
        $messagesGet1User1 = $this->messagingService->getAllMessagesFromThreadAndReactions(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[0]->getId()
        );
        $messagesGet1User2 = $this->messagingService->getAllMessagesFromThreadAndReactions(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(3, $messagesGet1User1);
        $this->assertCount(3, $messagesGet1User2);

        for ($i = 0; $i < count($messagesGet1User1); $i++) {
            $this->assertEquals($this->messageThreadIds[0], $messagesGet1User1[$i]->getMessageThreadId());
            $this->assertEquals($this->messageThreadIds[0], $messagesGet1User2[$i]->getMessageThreadId());
        }

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
    public function testCanDeleteAMessageFromTheMessageTableForAUser() {

        // user 1 sends a message to itself so 1 row is created
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
    public function testCanDeleteAMessageFromTheMessageTableForAllUsers() {

        // user 1 sends a message to itself so 1 row is created
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
            messageId: $messageRow->getMessageId()
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
        $this->assertEquals(0, $messageThreadGet->getMessageCount());
    }

    public function testCanGetAllMessageThreadsAtDifferentLevels() {

        $messageThreads = [
            new MessageThreadSummary(
                messageThreadUserId1: 99,
                messageThreadUserId2: 100,
                messageThreadAccountId: null,
                messageThreadGroupId: null
            ),
            new MessageThreadSummary(
                messageThreadUserId1: null,
                messageThreadUserId2: null,
                messageThreadAccountId: 100,
                messageThreadGroupId: null
            ),
            new MessageThreadSummary(
                messageThreadUserId1: null,
                messageThreadUserId2: null,
                messageThreadAccountId: null,
                messageThreadGroupId: 100
            )
        ];

        // save the message threads
        $messageThread1 = $this->messagingService->saveMessageThread($messageThreads[0]);
        $messageThread2 = $this->messagingService->saveMessageThread($messageThreads[1]);
        $messageThread3 = $this->messagingService->saveMessageThread($messageThreads[2]);

        // get the user level thread
        $userThreadsUser1 = $this->messagingService->getAllMessageThreadsByUserId(99);
        $userThreadsUser2 = $this->messagingService->getAllMessageThreadsByUserId(100);
        $this->assertCount(1, $userThreadsUser1);
        $this->assertCount(1, $userThreadsUser2);
        $this->assertEquals($messageThread1, $userThreadsUser1[0]->getId());
        $this->assertEquals($messageThread1, $userThreadsUser2[0]->getId());

        // get the account level thread
        $accountThreads = $this->messagingService->getAllMessageThreadsByAccountId(100);
        $this->assertCount(1, $accountThreads);
        $this->assertEquals($messageThread2, $accountThreads[0]->getId());

        // get the group level thread
        $groupThreads = $this->messagingService->getAllMessageThreadsByGroupId(100);
        $this->assertCount(1, $groupThreads);
        $this->assertEquals($messageThread3, $groupThreads[0]->getId());
    }

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

}
