<?php

namespace Kiniauth\Test\Services\Communication\Messaging;


use DateTime;
use Kiniauth\Exception\Security\InvalidLoginException;
use Kiniauth\Objects\Communication\Messaging\MessageSummary;
use Kiniauth\Objects\Communication\Messaging\MessageThread;
use Kiniauth\Objects\Communication\Messaging\MessageThreadSummary;
use Kiniauth\Services\Communication\Messaging\MessagingService;
use Kiniauth\Services\Security\AuthenticationService;
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


    /**
     * @throws InvalidLoginException
     */
    public function setUp(): void {
        parent::setUp();

        $authenticationService = Container::instance()->get(AuthenticationService::class);
        $authenticationService->login("sam@samdavisdesign.co.uk", AuthenticationHelper::encryptPasswordForLogin("passwordsam@samdavisdesign.co.uk"));

        $this->messagingService = new MessagingService();
    }

    public function testMessagesCanBeSavedAndRetrieved() {

        $messageId = $this->messagingService->saveMessage(
            new MessageSummary(
                1,
                "hello world!",
                MessageType::General,
                1,
                null,
                2,
                null,
                null,
            )
        );

        $this->assertEquals(1, $messageId);

        $messageGet = $this->messagingService->getMessageSummaryById($messageId);

        $this->assertEquals(1, $messageGet->getId());
        $this->assertEquals(1, $messageGet->getMessageThreadId());
        $this->assertEquals("hello world!", $messageGet->getEncryptedMessage());
        $this->assertEquals("general", $messageGet->getMessageType()->value);
        $this->assertEquals(1, $messageGet->getSenderUserId());
        $this->assertEquals(null, $messageGet->getSenderAccountId());
        $this->assertEquals(2, $messageGet->getReceiverUserId());
        $this->assertEquals(null, $messageGet->getReceiverAccountId());
        $this->assertEquals(null, $messageGet->getReceiverGroupId());
        $this->assertNotNull($messageGet->getMessageDate());
    }

    public function testCanGetAllMessagesFromAThread() {

        $messages = [
            new MessageSummary(
                1,
                "hello world!",
                MessageType::General,
                1,
                null,
                2,
                null,
                null,
            ),
            new MessageSummary(
                1,
                "hello back!",
                MessageType::General,
                2,
                null,
                1,
                null,
                null,
            ),
            new MessageSummary(
                1,
                "this is exciting!",
                MessageType::General,
                1,
                null,
                2,
                null,
                null,
            ),
            new MessageSummary(
                2,
                "ignore this message!",
                MessageType::General,
                1,
                null,
                2,
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
        $messagesGet = $this->messagingService->getAllMessagesFromThread(1);

        $this->assertCount(3, $messagesGet);

        foreach($messagesGet as $messageGet) {
            $this->assertEquals(1, $messageGet->getMessageThreadId());
        }

    }

    public function testCanGetLatestMessageFromAThread() {

        $messages = [
            new MessageSummary(
                1,
                "hello world!",
                MessageType::General,
                1,
                null,
                2,
                null,
                null,
            ),
            new MessageSummary(
                1,
                "hello back!",
                MessageType::General,
                2,
                null,
                1,
                null,
                null,
            ),
            new MessageSummary(
                1,
                "this is exciting!",
                MessageType::General,
                1,
                null,
                2,
                null,
                null,
            ),
            new MessageSummary(
                2,
                "ignore this message!",
                MessageType::General,
                1,
                null,
                2,
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
        $messagesGet1 = $this->messagingService->getLatestMessageFromThread(1);
        $messagesGet2 = $this->messagingService->getLatestMessageFromThread(2);

        $this->assertEquals(3, $messagesGet1->getId());
        $this->assertEquals(4, $messagesGet2->getId());
    }

    public function testCanDeleteAMessage() {

        $messageId = $this->messagingService->saveMessage(
            new MessageSummary(
                1,
                "hello world!",
                MessageType::General,
                1,
                null,
                2,
                null,
                null,
            )
        );

        $this->assertEquals(1, $messageId);

        // delete the message we just created
        $this->messagingService->deleteMesssage($messageId);

        $this->expectException(ObjectNotFoundException::class);
        $this->messagingService->getMessageSummaryById($messageId);
    }

    public function testCanCreateNewMessageThread() {

        $threadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                1,
                1,
                null,
                null
            )
        );

        $this->assertEquals(1, $threadId);

        $messageThreadGet = $this->messagingService->getMessageThreadSummaryById($threadId);

        $this->assertEquals(1, $messageThreadGet->getMessageThreadId());
        $this->assertEquals(1, $messageThreadGet->getMessageThreadUserId());
        $this->assertEquals(null, $messageThreadGet->getMessageThreadAccountId());
        $this->assertEquals(null, $messageThreadGet->getMessageThreadGroupId());
        $this->assertEquals(1, $messageThreadGet->getId());
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
                2,
                null,
                1,
                null
            ),
            new MessageThreadSummary(
                3,
                null,
                null,
                1
            )
        ];

        // save the message threads
        $this->messagingService->saveMessageThread($messageThreads[0]);
        $this->messagingService->saveMessageThread($messageThreads[1]);
        $this->messagingService->saveMessageThread($messageThreads[2]);

        // get the user level thread
        $userThreads = $this->messagingService->getMessageThreadByUserId(1);
        $this->assertCount(1, $userThreads);
        $this->assertEquals(1, $userThreads[0]->getMessageThreadId());

        // get the account level thread
        $accountThreads = $this->messagingService->getMessageThreadByAccountId(1);
        $this->assertCount(1, $accountThreads);
        $this->assertEquals(2, $accountThreads[0]->getMessageThreadId());

        // get the group level thread
        $groupThreads = $this->messagingService->getMessageThreadByGroupId(1);
        $this->assertCount(1, $groupThreads);
        $this->assertEquals(3, $groupThreads[0]->getMessageThreadId());
    }

    public function testCanDeleteAMessageThread() {
        $threadId = $this->messagingService->saveMessageThread(
            new MessageThreadSummary(
                1,
                1,
                null,
                null
            )
        );

        $this->assertEquals(1, $threadId);

        $this->messagingService->deleteMessageThread($threadId);

        $this->expectException(ObjectNotFoundException::class);
        $this->messagingService->getMessageThreadSummaryById($threadId);

    }

}
