<?php

namespace Kiniauth\Test\Services\Communication\Messaging;


use Exception;
use Kiniauth\Exception\Security\InvalidLoginException;
use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Objects\Communication\Messaging\MessageSummary;
use Kiniauth\Objects\Communication\Messaging\MessageThread;
use Kiniauth\Objects\Communication\Messaging\MessageThreadSummary;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Communication\Messaging\MessagingService;
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
    private array $userIds;
    private array $messageThreadIds;

    /**
     * @throws InvalidLoginException
     */
    public function setUp(): void {
        parent::setUpBeforeClass();

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $this->messagingService = new MessagingService();

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
        $this->userIds = [
            $userId1,
            $userId2
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

        $userService = Container::instance()->get(UserService::class);

        foreach ($this->userIds as $userId) {
            $user = $userService->getUser($userId);
            $user->remove();
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
                messageType: MessageType::General,
                senderUserId: $this->userIds[0],
                senderAccountId: null,
                receiverUserId: $this->userIds[1],
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        // check that the message was saved to the sender user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->userIds[0]
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[0], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType()->value);
        $this->assertEquals($this->userIds[0], $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->userIds[0], $messageGet[0]->getReceiverUserId());
        $this->assertNotNull($messageGet[0]->getMessageDate());

        // check that the message was saved to the receiver user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->userIds[1]
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[0], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType()->value);
        $this->assertEquals($this->userIds[0], $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->userIds[1], $messageGet[0]->getReceiverUserId());
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
                messageType: MessageType::General,
                senderUserId: $this->userIds[0],
                senderAccountId: null,
                receiverUserId: null,
                receiverAccountId: 99,
                receiverGroupId: null,
            )
        );

        // check that the message was saved to all the users of the account
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->userIds[0]
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[2], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType()->value);
        $this->assertEquals($this->userIds[0], $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->userIds[0], $messageGet[0]->getReceiverUserId());
        $this->assertEquals(99, $messageGet[0]->getReceiverAccountId());
        $this->assertNotNull($messageGet[0]->getMessageDate());

        // check that the message was saved to the receiver user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThread(
            threadId: $this->messageThreadIds[2],
            userId: $this->userIds[1]
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[2], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType()->value);
        $this->assertEquals($this->userIds[0], $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->userIds[1], $messageGet[0]->getReceiverUserId());
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
                messageType: MessageType::General,
                senderUserId: $this->userIds[0],
                senderAccountId: null,
                receiverUserId: null,
                receiverAccountId: null,
                receiverGroupId: 99,
            )
        );

        // check that the message was saved to the sender user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->userIds[0]
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[3], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType()->value);
        $this->assertEquals($this->userIds[0], $messageGet[0]->getSenderUserId());
        $this->assertEquals(99, $messageGet[0]->getReceiverGroupId());
        $this->assertNotNull($messageGet[0]->getMessageDate());

        // check that the message was saved to the receiver user
        /** @var MessageSummary[] $messageGet */
        $messageGet = $this->messagingService->getAllMessagesFromThread(
            threadId: $this->messageThreadIds[3],
            userId: $this->userIds[1]
        );

        $this->assertCount(1, $messageGet);

        $this->assertEquals($this->messageThreadIds[3], $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType()->value);
        $this->assertEquals($this->userIds[0], $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->userIds[1], $messageGet[0]->getReceiverUserId());
        $this->assertEquals(99, $messageGet[0]->getReceiverGroupId());
        $this->assertNotNull($messageGet[0]->getMessageDate());
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
                    messageType: MessageType::General,
                    senderUserId: 99,
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
                    messageType: MessageType::General,
                    senderUserId: $this->userIds[0],
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
                    messageType: MessageType::General,
                    senderUserId: $this->userIds[0],
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
                messageType: MessageType::General,
                senderUserId: $this->userIds[0],
                senderAccountId: null,
                receiverUserId: $this->userIds[1],
                receiverAccountId: null,
                receiverGroupId: null,
            ),
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "hello back!",
                messageType: MessageType::General,
                senderUserId: $this->userIds[1],
                senderAccountId: null,
                receiverUserId: $this->userIds[0],
                receiverAccountId: null,
                receiverGroupId: null,
            ),
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "this is exciting!",
                messageType: MessageType::General,
                senderUserId: $this->userIds[0],
                senderAccountId: null,
                receiverUserId: $this->userIds[1],
                receiverAccountId: null,
                receiverGroupId: null,
            ),
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "ignore this message!",
                messageType: MessageType::General,
                senderUserId: $this->userIds[0],
                senderAccountId: null,
                receiverUserId: $this->userIds[1],
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
        $messagesGet1User1 = $this->messagingService->getAllMessagesFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->userIds[0]
        );
        $messagesGet1User2 = $this->messagingService->getAllMessagesFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->userIds[1]
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
                messageType: MessageType::General,
                senderUserId: $this->userIds[0],
                senderAccountId: null,
                receiverUserId: $this->userIds[1],
                receiverAccountId: null,
                receiverGroupId: null,
            ),
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "hello back!",
                messageType: MessageType::General,
                senderUserId: $this->userIds[1],
                senderAccountId: null,
                receiverUserId: $this->userIds[0],
                receiverAccountId: null,
                receiverGroupId: null,
            ),
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[0],
                encryptedMessage: "this is exciting!",
                messageType: MessageType::General,
                senderUserId: $this->userIds[0],
                senderAccountId: null,
                receiverUserId: $this->userIds[1],
                receiverAccountId: null,
                receiverGroupId: null,
            ),
            new MessageSummary(
                messageThreadId: $this->messageThreadIds[1],
                encryptedMessage: "ignore this message!",
                messageType: MessageType::General,
                senderUserId: $this->userIds[0],
                senderAccountId: null,
                receiverUserId: $this->userIds[1],
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
            userId: $this->userIds[0]
        );
        $messagesGet1User2 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->userIds[1]
        );
        $messagesGet2User1 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->userIds[0]
        );
        $messagesGet2User2 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[1],
            userId: $this->userIds[1]
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
                messageType: MessageType::General,
                senderUserId: $this->userIds[0],
                senderAccountId: null,
                receiverUserId: $this->userIds[1],
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        $messageRow = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->userIds[0]
        );

        // delete the message we just created
        $this->messagingService->deleteMessageForUser(
            threadId: $this->messageThreadIds[0],
            messageId: $messageRow->getMessageId(),
            userId: $this->userIds[0]
        );

        // check to see that the message was deleted for the first user
        $removedMessageUser1 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->userIds[0]
        );
        $this->assertNull($removedMessageUser1->getEncryptedMessage());

        // check to see that the message was not deleted for the second user
        $removedMessageUser2 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->userIds[1]
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
                messageType: MessageType::General,
                senderUserId: $this->userIds[0],
                senderAccountId: null,
                receiverUserId: $this->userIds[1],
                receiverAccountId: null,
                receiverGroupId: null,
            )
        );

        $messageRow = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->userIds[0]
        );

        // delete the message we just created
        $this->messagingService->deleteMessageForAllUsers(
            threadId: $this->messageThreadIds[0],
            messageId: $messageRow->getMessageId()
        );

        // check to see that the message was deleted for the first user
        $removedMessageUser1 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->userIds[0]
        );
        $this->assertNull($removedMessageUser1->getEncryptedMessage());

        // check to see that the message was deleted for the second user
        $removedMessageUser2 = $this->messagingService->getLatestMessageFromThread(
            threadId: $this->messageThreadIds[0],
            userId: $this->userIds[1]
        );
        $this->assertNull($removedMessageUser2->getEncryptedMessage());
    }

    public function testCanCreateNewMessageThread() {

        $threadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                messageThreadUserId1: $this->userIds[0],
                messageThreadUserId2: $this->userIds[1],
                messageThreadAccountId: null,
                messageThreadGroupId: null
            )
        );

        // get the message thread summary we just created
        $messageThreadGet = $this->messagingService->getMessageThreadSummaryById($threadId);

        // check it looks correct
        $this->assertEquals($this->userIds[0], $messageThreadGet->getMessageThreadUserId1());
        $this->assertEquals($this->userIds[1], $messageThreadGet->getMessageThreadUserId2());
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
                messageThreadUserId1: $this->userIds[0],
                messageThreadUserId2: $this->userIds[1],
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
