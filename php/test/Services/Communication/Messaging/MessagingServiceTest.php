<?php

namespace Kiniauth\Test\Services\Communication\Messaging;


use DateTime;
use Kiniauth\Objects\Communication\Messaging\MessageSummary;
use Kiniauth\Services\Communication\Messaging\MessagingService;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\Communication\Messaging\MessageType;
use Kinikit\Core\DependencyInjection\Container;

include_once __DIR__ . "/../../../autoloader.php";

class MessagingServiceTest extends TestBase {

    /**
     * @var MessagingService
     */
    private $messagingService;


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
                2,
                null,
                null,
            ),
            new MessageSummary(
                1,
                "hello back!",
                MessageType::General,
                2,
                1,
                null,
                null,
            ),
            new MessageSummary(
                1,
                "this is exciting!",
                MessageType::General,
                1,
                2,
                null,
                null,
            ),
            new MessageSummary(
                2,
                "ignore this message!",
                MessageType::General,
                1,
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

}
