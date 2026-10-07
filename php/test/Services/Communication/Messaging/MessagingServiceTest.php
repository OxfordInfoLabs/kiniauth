<?php

namespace Kiniauth\Test\Services\Communication\Messaging;


use Exception;
use Kiniauth\Exception\Security\InvalidLoginException;
use Kiniauth\Objects\Communication\Messaging\MessageSummary;
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

    /**
     * @throws InvalidLoginException
     */
    public function setUp(): void {
        parent::setUpBeforeClass();

        AuthenticationHelper::login("admin@kinicart.com", "password");

        // create a test group with test users
        $userService = Container::instance()->get(UserService::class);

        $userId1 = $userService->createUser(
            "hello@myworld.com",
            hash("sha512", "newpassword1"),
            "Hello World",
            new UserRole(Role::SCOPE_ACCOUNT, 99, 0, 99)
        );

        $userService->updateUserPersonalEncryptionKey(
            "test-personal-encryption-key",
            $userId1
        );

        $userId2 = $userService->createUser(
            "happy@myworld.com",
            hash("sha512", "newpassword1"),
            "Happy World",
            new UserRole(Role::SCOPE_ACCOUNT, 99, 0, 99)
        );

        $userService->updateUserPersonalEncryptionKey(
            "another-test-personal-encryption-key",
            $userId2
        );

        $this->userIds = [
            $userId1,
            $userId2
        ];

        $this->messagingService = new MessagingService();
    }

    public function tearDown(): void {
        parent::tearDownAfterClass();

        $userService = Container::instance()->get(UserService::class);

        foreach ($this->userIds as $userId) {
            $user = $userService->getUser($userId);
            $user->remove();
        }
    }

    /**
     * @throws Exception
     */
    public function testMessagesCanBeSavedAndRetrievedAtTheUserLevel() {

        $this->messagingService->saveMessage(
            new MessageSummary(
                1,
                "hello world!",
                MessageType::General,
                $this->userIds[0],
                null,
                $this->userIds[1],
                null,
                null,
            )
        );

        // check that the message was saved to the sender user
        $messageGet = $this->messagingService->getAllMessagesFromThread(1, $this->userIds[0]);

        $this->assertCount(1, $messageGet);

        $this->assertEquals(1, $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType()->value);
        $this->assertEquals($this->userIds[0], $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->userIds[0], $messageGet[0]->getReceiverUserId());
        $this->assertNotNull($messageGet[0]->getMessageDate());

        // check that the message was saved to the receiver user
        $messageGet = $this->messagingService->getAllMessagesFromThread(1, $this->userIds[1]);

        $this->assertCount(1, $messageGet);

        $this->assertEquals(1, $messageGet[0]->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet[0]->getEncryptedMessage());
        $this->assertEquals("general", $messageGet[0]->getMessageType()->value);
        $this->assertEquals($this->userIds[0], $messageGet[0]->getSenderUserId());
        $this->assertEquals($this->userIds[1], $messageGet[0]->getReceiverUserId());
        $this->assertNotNull($messageGet[0]->getMessageDate());
    }

    /**
     * @throws Exception
     */
    public function testCanGetAllMessagesFromAGivenThread(): void {

        $messages = [
            new MessageSummary(
                1,
                "hello world!",
                MessageType::General,
                $this->userIds[0],
                null,
                $this->userIds[1],
                null,
                null,
            ),
            new MessageSummary(
                1,
                "hello back!",
                MessageType::General,
                $this->userIds[1],
                null,
                $this->userIds[0],
                null,
                null,
            ),
            new MessageSummary(
                1,
                "this is exciting!",
                MessageType::General,
                $this->userIds[0],
                null,
                $this->userIds[1],
                null,
                null,
            ),
            new MessageSummary(
                2,
                "ignore this message!",
                MessageType::General,
                $this->userIds[0],
                null,
                $this->userIds[1],
                null,
                null,
            )
        ];

        // save all the messages
        $this->messagingService->saveMessage($messages[0]);
        $this->messagingService->saveMessage($messages[1]);
        $this->messagingService->saveMessage($messages[2]);
        $this->messagingService->saveMessage($messages[3]);

        // retrieve all the messages from thread 1
        $messagesGet1User1 = $this->messagingService->getAllMessagesFromThread(1, $this->userIds[0]);
        $messagesGet1User2 = $this->messagingService->getAllMessagesFromThread(1, $this->userIds[1]);

        $this->assertCount(3, $messagesGet1User1);
        $this->assertCount(3, $messagesGet1User2);

        for ($i = 0; $i < count($messagesGet1User1); $i++) {
            $this->assertEquals(1, $messagesGet1User1[$i]->getMessageThreadId());
            $this->assertEquals(1, $messagesGet1User2[$i]->getMessageThreadId());
        }

    }

    /**
     * @throws Exception
     */
    public function testCanGetLatestMessageFromAThread() {

        $messages = [
            new MessageSummary(
                1,
                "hello world!",
                MessageType::General,
                $this->userIds[0],
                null,
                $this->userIds[1],
                null,
                null,
            ),
            new MessageSummary(
                1,
                "hello back!",
                MessageType::General,
                $this->userIds[1],
                null,
                $this->userIds[0],
                null,
                null,
            ),
            new MessageSummary(
                1,
                "this is exciting!",
                MessageType::General,
                $this->userIds[0],
                null,
                $this->userIds[1],
                null,
                null,
            ),
            new MessageSummary(
                2,
                "ignore this message!",
                MessageType::General,
                $this->userIds[0],
                null,
                $this->userIds[1],
                null,
                null,
            )
        ];

        // save all the messages
        $this->messagingService->saveMessage($messages[0]);
        $this->messagingService->saveMessage($messages[1]);
        $this->messagingService->saveMessage($messages[2]);
        $this->messagingService->saveMessage($messages[3]);

        // retrieve all the messages from threads 1 and 2
        $messagesGet1User1 = $this->messagingService->getLatestMessageFromThread(1, $this->userIds[0]);
        $messagesGet1User2 = $this->messagingService->getLatestMessageFromThread(1, $this->userIds[1]);
        $messagesGet2User1 = $this->messagingService->getLatestMessageFromThread(2, $this->userIds[0]);
        $messagesGet2User2 = $this->messagingService->getLatestMessageFromThread(2, $this->userIds[1]);

        $this->assertEquals("this is exciting!", $messagesGet1User1->getEncryptedMessage());
        $this->assertEquals("this is exciting!", $messagesGet1User2->getEncryptedMessage());
        $this->assertEquals("ignore this message!", $messagesGet2User1->getEncryptedMessage());
        $this->assertEquals("ignore this message!", $messagesGet2User2->getEncryptedMessage());
    }

    /**
     * @throws Exception
     */
    public function testCanDeleteAMessageFromTheMessageTable() {

        // user 1 sends a message to itself so 1 row is created
        $this->messagingService->saveMessage(
            new MessageSummary(
                1,
                "hello world!",
                MessageType::General,
                $this->userIds[0],
                null,
                $this->userIds[0],
                null,
                null,
            )
        );

        $messageRow = $this->messagingService->getLatestMessageFromThread(1, $this->userIds[0]);

        // delete the message we just created
        $this->messagingService->deleteMessage($messageRow->getId());

        $this->expectException(ObjectNotFoundException::class);
        $this->messagingService->getMessageSummaryById($messageRow->getId());
    }

    public function testCanCreateNewMessageThread() {

        $threadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                $this->userIds[0],
                $this->userIds[1],
                null,
                null
            )
        );

        $messageThreadGet = $this->messagingService->getMessageThreadSummaryById($threadId);

        $this->assertEquals($this->userIds[0], $messageThreadGet->getMessageThreadUserId1());
        $this->assertEquals($this->userIds[1], $messageThreadGet->getMessageThreadUserId2());
        $this->assertEquals(null, $messageThreadGet->getMessageThreadAccountId());
        $this->assertEquals(null, $messageThreadGet->getMessageThreadGroupId());
    }

    public function testCanGetAllMessageThreadsAtDifferentLevels() {

        $messageThreads = [
            new MessageThreadSummary(
                1,
                1,
                null,
                null
            ),
            new MessageThreadSummary(
                null,
                null,
                1,
                null
            ),
            new MessageThreadSummary(
                null,
                null,
                null,
                1
            )
        ];

        // save the message threads
        $messageThread1 = $this->messagingService->saveMessageThread($messageThreads[0]);
        $messageThread2 = $this->messagingService->saveMessageThread($messageThreads[1]);
        $messageThread3 = $this->messagingService->saveMessageThread($messageThreads[2]);

        // get the user level thread
        $userThreads = $this->messagingService->getAllMessageThreadsByUserId(1);
        $this->assertCount(1, $userThreads);
        $this->assertEquals($messageThread1, $userThreads[0]->getMessageThreadId());

        // get the account level thread
        $accountThreads = $this->messagingService->getAllMessageThreadsByAccountId(1);
        $this->assertCount(1, $accountThreads);
        $this->assertEquals($messageThread2, $accountThreads[0]->getMessageThreadId());

        // get the group level thread
        $groupThreads = $this->messagingService->getAllMessageThreadsByGroupId(1);
        $this->assertCount(1, $groupThreads);
        $this->assertEquals($messageThread3, $groupThreads[0]->getMessageThreadId());
    }

    public function testCanDeleteAMessageThread() {
        $threadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                $this->userIds[0],
                $this->userIds[1],
                null,
                null
            )
        );

        $this->messagingService->deleteMessageThread($threadId);

        $this->expectException(ObjectNotFoundException::class);
        $this->messagingService->getMessageThreadSummaryById($threadId);

    }

}
