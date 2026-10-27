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

    /**
     * @var MessagingService
     */
    private $messagingService;
    private array $users;
    private array $messageThreadIds;
    private UserService $userService;
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

        // link the account to a group
        $accountGroupMember = new AccountGroupMember(99, 99);
        $accountGroupMember->save();

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
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
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
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
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
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
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
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
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
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
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
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
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
    public function testCannotSaveMessageWhenSenderIsNotInUserThread() {

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
                    receiverUserId: $this->users[1]->getId(),
                    receiverAccountId: null,
                    receiverGroupId: null,
                )
            );

            $this->fail("Expected exception was not thrown");
        } catch (Exception $e) {
            $this->assertEquals("Sender user id does not match thread user ids.", $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveMessageWhenRecipientIsNotInUserThread() {

        // user level
        try {
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
        } catch (Exception $e) {
            $this->assertEquals("Receiver user id does not match thread user ids.", $e->getMessage());
        }


    }

    /**
     * @throws Exception
     */
    public function testCannotSaveMessageWhenAccountDoesNotMatchThread() {

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
            $this->assertEquals("Receiver account id does not match thread account id.", $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testCannotSaveMessageWhenGroupDoesNotMatchThread() {

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
            $this->assertEquals("Receiver group id does not match thread group id.", $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testMessagesCannotBeSavedWithoutAThreadId() {


        try {
            $this->messagingService->saveMessage(
                new MessageSummary(
                    messageThreadId: null, //unknown thread ID
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
        } catch (Exception $e) {
            $this->assertEquals("Message thread id is required to save a message", $e->getMessage());
        }

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
        } catch (Exception $e) {
            $this->assertEquals("At least one recipient parameter must be provided.", $e->getMessage());
        }

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
        } catch (Exception $e) {
            $this->assertEquals("No valid users present for saving message", $e->getMessage());
        }

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
                $userId,
                $userId,
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
                    senderUserId: $userId,
                    senderUserName: null,
                    senderAccountId: null,
                    receiverUserId: $userId,
                    receiverAccountId: null,
                    receiverGroupId: null,
                )
            );
            $this->fail("Exception should have been thrown");
        } catch (Exception $e) {
            $this->assertEquals("User (ID: {$userId}) does not have a personal encryption key", $e->getMessage());
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

        // only retrieve the message for the receiver user
        $lastMessageId = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        )->getMessageId();

        // get the encrypted message for the receiver user
        $storedMessage1 = Message::filter(
            "WHERE messageThreadId = ? AND messageId = ? AND receiverUserId = ?",
            [$this->messageThreadIds[0], $lastMessageId, $this->users[1]->getId()]
        );

        $encryptedMessage1 = $storedMessage1[0]->getEncryptedMessage();

        // check that the message was saved to the receiver user
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

        $decryptedMessage1 = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext1,
            '',
            $nonce1,
            $derivedKey1
        );

        $decryptedMessage2 = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext2,
            '',
            $nonce2,
            $derivedKey2
        );

        $this->assertNotEquals("hello world!", $decryptedMessage1);
        $this->assertEquals("hello world!", $decryptedMessage2);

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
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[1], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $messageGet[0]->getSenderUserName());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getReceiverUserId());
        $this->assertNotNull($messageGet[0]->getMessageDate());

        // check that the message was saved to the receiver user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[1], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType());
        $this->assertEquals($this->users[0]->getId(), $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->users[0]->getName(), $messageGet[0]->getSenderUserName());
        $this->assertEquals($this->users[1]->getId(), $messageGet[0]->getReceiverUserId());
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
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $messageGet);
        $this->assertCount(1, $messageGet[0]->getMessageReactions());
        $this->assertEquals("👍", $messageGet[0]->getMessageReactions()[0]->getReaction());

        // retrieve the message and reaction for the receiver user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $messageGet);
        $this->assertCount(1, $messageGet[0]->getMessageReactions());
        $this->assertEquals("👍", $messageGet[0]->getMessageReactions()[0]->getReaction());
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
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
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
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
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
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $messageGet);
        $this->assertCount(1, $messageGet[0]->getMessageReactions());
        $this->assertEquals("👍", $messageGet[0]->getMessageReactions()[0]->getReaction());

        // retrieve the message and reaction for the receiver user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[1]->getId()
        );

        $this->assertCount(1, $messageGet);
        $this->assertCount(1, $messageGet[0]->getMessageReactions());
        $this->assertEquals("👍", $messageGet[0]->getMessageReactions()[0]->getReaction());
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
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
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
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
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
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $messageGet);
        $this->assertCount(1, $messageGet[0]->getMessageReactions());
        $this->assertEquals("👍", $messageGet[0]->getMessageReactions()[0]->getReaction());

        // retrieve the message and reaction for the receiver user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
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

        // check that the message was saved to the sender user
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        // save a reaction to this message
        $this->messagingService->saveMessageReaction(
            new MessageReactionSummary(
                messageId: $messageGet->getMessageId(),
                messageThreadId: $messageGet->getMessageThreadId(),
                reaction: "👍",
                senderUserId: $this->users[0]->getId(),
                senderUserName: $this->users[0]->getName(),
            )
        );

        // retrieve the message and reaction for the sender user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $messageGet);
        $this->assertCount(1, $messageGet[0]->getMessageReactions());
        $this->assertEquals("👍", $messageGet[0]->getMessageReactions()[0]->getReaction());

        // delete the reaction
        $this->messagingService->deleteMessageReaction(
            threadId: $messageGet[0]->getMessageThreadId(),
            messageId: $messageGet[0]->getMessageId(),
            userId: $this->users[0]->getId(),
        );

        // retrieve the message and reaction for the sender user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesAndReactionsFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertCount(1, $messageGet);
        $this->assertEmpty($messageGet[0]->getMessageReactions());
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
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertEquals($this->messageThreadIds[1], $messageGet->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet->getEncryptedMessage());

        // check that the message was saved to the receiver user
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[1], $messageGet->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet->getEncryptedMessage());

        // edit the message to something new
        $this->messagingService->editMessage(
            threadId: $this->messageThreadIds[1],
            messageId: $messageGet->getMessageId(),
            newMessage: "goodbye world!",
            userId: $this->users[0]->getId()
        );

        // check again to see if the change persists
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[0]->getId()
        );

        $this->assertEquals($this->messageThreadIds[1], $messageGet->getMessageThreadId());
        $this->assertEquals("goodbye world!", $messageGet->getEncryptedMessage());

        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[1], $messageGet->getMessageThreadId());
        $this->assertEquals("goodbye world!", $messageGet->getEncryptedMessage());
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
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[0]->getId()
        );

        $this->assertEquals($this->messageThreadIds[2], $messageGet->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet->getEncryptedMessage());

        // check that the message was saved to the receiver user
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[2], $messageGet->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet->getEncryptedMessage());

        // edit the message to something new
        $this->messagingService->editMessage(
            threadId: $this->messageThreadIds[2],
            messageId: $messageGet->getMessageId(),
            newMessage: "goodbye world!",
            userId: $this->users[0]->getId()
        );

        // check again to see if the change persists
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[0]->getId()
        );

        $this->assertEquals($this->messageThreadIds[2], $messageGet->getMessageThreadId());
        $this->assertEquals("goodbye world!", $messageGet->getEncryptedMessage());

        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[2], $messageGet->getMessageThreadId());
        $this->assertEquals("goodbye world!", $messageGet->getEncryptedMessage());
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

        // check that the message was saved to the receiver user
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[3], $messageGet->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet->getEncryptedMessage());

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

        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertEquals($this->messageThreadIds[3], $messageGet->getMessageThreadId());
        $this->assertEquals("goodbye world!", $messageGet->getEncryptedMessage());
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


        // edit the message to something new
        // using the receiver's user id
        try{
            $this->messagingService->editMessage(
                threadId: $this->messageThreadIds[1],
                messageId: $messageId,
                newMessage: "goodbye world!",
                userId: $this->users[1]->getId()
            );
            $this->fail("Exception not thrown when editing message");
        } catch (Exception $e) {
            $this->assertEquals("User (ID: {$this->users[1]->getId()}) is not the sender of the message (ID: {$messageId})."
                . " Only the sender can edit a message.", $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testNonExistentMessagesCannotBeEdited() {

        // edit the message to something new
        // using the receiver's user id
        try{
            $this->messagingService->editMessage(
                threadId: $this->messageThreadIds[1],
                messageId: 99,
                newMessage: "goodbye world!",
                userId: $this->users[1]->getId()
            );
            $this->fail("Exception not thrown when editing message");
        } catch (Exception $e) {
            $this->assertEquals("Message (ID: 99) not found in thread (ID: {$this->messageThreadIds[1]}).", $e->getMessage());
        }
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
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[0]->getId()
        );


        $this->assertTrue($messageGet->getMessageRead());

        // check that the message was saved to the receiver user
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        );

        $this->assertFalse($messageGet->getMessageRead());

        // mark the message as read for the receiver user
        $this->messagingService->markMessageRead(
            threadId: $this->messageThreadIds[0],
            messageId: $messageGet->getMessageId(),
            userId: $this->users[1]->getId()
        );

        // check that this has updated for the receiver user
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->users[1]->getId()
        );

        $this->assertTrue($messageGet->getMessageRead());
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
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[0]->getId()
        );


        $this->assertTrue($messageGet->getMessageRead());

        // check that the message was saved to the receiver user
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[1]->getId()
        );

        $this->assertFalse($messageGet->getMessageRead());

        // mark the message as read for the receiver user
        $this->messagingService->markMessageRead(
            threadId: $this->messageThreadIds[2],
            messageId: $messageGet->getMessageId(),
            userId: $this->users[1]->getId()
        );

        // check that this has updated for the receiver user
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->users[1]->getId()
        );

        $this->assertTrue($messageGet->getMessageRead());
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
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[0]->getId()
        );


        $this->assertTrue($messageGet->getMessageRead());

        // check that the message was saved to the receiver user
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertFalse($messageGet->getMessageRead());

        // mark the message as read for the receiver user
        $this->messagingService->markMessageRead(
            threadId: $this->messageThreadIds[3],
            messageId: $messageGet->getMessageId(),
            userId: $this->users[1]->getId()
        );

        // check that this has updated for the receiver user
        /** @var MessageSummary $messageGet */
        $messageGet = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->users[1]->getId()
        );

        $this->assertTrue($messageGet->getMessageRead());
    }

    //
    // Message deletion
    //

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

    //
    // Saving and retrieving message threads
    //

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

    //
    // Deleting message threads
    //

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
