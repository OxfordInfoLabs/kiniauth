<?php


namespace Kiniauth\Test\Services\Attachment;


use Kiniauth\Objects\Attachment\Attachment;
use Kiniauth\Objects\Attachment\AttachmentSummary;
use Kiniauth\Services\Attachment\AttachmentService;
use Kiniauth\Services\Attachment\AttachmentStorage;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\MVC\ContentSource\ReadableStreamContentSource;
use Kinikit\MVC\Response\Download;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

include_once "autoloader.php";

class AttachmentServiceTest extends TestBase {

    /**
     * @var AttachmentService
     */
    private $attachmentService;

    /**
     * @var MockObject
     */
    private $testAttachmentStorage;


    /**
     * Set up
     */
    public function setUp(): void {
        $this->attachmentService = new AttachmentService();

        $this->testAttachmentStorage = MockObjectProvider::instance()->getMockInstance(AttachmentStorage::class);
        Container::instance()->addInterfaceImplementation(AttachmentStorage::class, "test", get_class($this->testAttachmentStorage));
        Container::instance()->set(get_class($this->testAttachmentStorage), $this->testAttachmentStorage);

    }


    public function testCanSaveAttachmentAndItIsStoredInDatabaseAndStorageCalledWithContentStream() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $attachmentSummary = new AttachmentSummary("test.pdf", "text/pdf", "Contact", 12, "test", "myproject", 3);
        $contentStream = new ReadOnlyStringStream("Mark Test");

        $attachmentId = $this->attachmentService->saveAttachment($attachmentSummary, $contentStream);

        /**
         * @var Attachment $attachment
         */
        $attachment = Attachment::fetch($attachmentId);

        $this->assertEquals("test.pdf", $attachment->getAttachmentFilename());
        $this->assertEquals("text/pdf", $attachment->getMimeType());
        $this->assertEquals("Contact", $attachment->getParentObjectType());
        $this->assertEquals(12, $attachment->getParentObjectId());
        $this->assertEquals("test", $attachment->getStorageKey());
        $this->assertEquals((new \DateTime())->format("Y-m-d H:i"), $attachment->getCreatedDate()->format("Y-m-d H:i"));
        $this->assertEquals((new \DateTime())->format("Y-m-d H:i"), $attachment->getUpdatedDate()->format("Y-m-d H:i"));
        $this->assertEquals("myproject", $attachment->getProjectKey());
        $this->assertEquals(3, $attachment->getAccountId());
        $this->assertEquals("", $attachment->getContent());

        $this->assertTrue($this->testAttachmentStorage->methodWasCalled("saveAttachmentContent", [
            $attachment, $contentStream
        ]));


    }


    public function testCanRemoveAttachmentByIdAndTheStorageIsCalledToDeleteItToo() {


        AuthenticationHelper::login("admin@kinicart.com", "password");

        $attachmentSummary = new AttachmentSummary("test.pdf", "text/pdf", "Contact", 12, "test", "myproject", 3);
        $contentStream = new ReadOnlyStringStream("Mark Test");

        $attachmentId = $this->attachmentService->saveAttachment($attachmentSummary, $contentStream);
        $attachment = Attachment::fetch($attachmentId);

        $this->attachmentService->removeAttachment($attachmentId);

        // Check none existent
        try {
            Attachment::fetch($attachmentId);
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
        }

        $this->assertTrue($this->testAttachmentStorage->methodWasCalled("removeAttachmentContent", [$attachment]));

    }


    public function testCanStreamAttachmentContentUsingStorage() {


        AuthenticationHelper::login("admin@kinicart.com", "password");

        $attachmentSummary = new AttachmentSummary("test.pdf", "text/pdf", "Contact", 12, "test", "myproject", 3);
        $contentStream = new ReadOnlyStringStream("Mark Test");

        $attachmentId = $this->attachmentService->saveAttachment($attachmentSummary, $contentStream);
        $attachment = Attachment::fetch($attachmentId);

        $expectedStream = new ReadOnlyStringStream("Bingo Bongo");
        $this->testAttachmentStorage->returnValue("streamAttachmentContent", $expectedStream, [$attachment]);

        $stream = $this->attachmentService->streamAttachment($attachmentId);
        $this->assertEquals($expectedStream, $stream);

    }

    public function testCanStreamAttachmentContentUsingStorageAsDownloadIfFlagSupplied() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $attachmentSummary = new AttachmentSummary("test.pdf", "text/pdf", "Contact", 12, "test", "myproject", 3);
        $contentStream = new ReadOnlyStringStream("Mark Test");

        $attachmentId = $this->attachmentService->saveAttachment($attachmentSummary, $contentStream);
        $attachment = Attachment::fetch($attachmentId);

        $expectedStream = new ReadOnlyStringStream("Bingo Bongo");
        $this->testAttachmentStorage->returnValue("streamAttachmentContent", $expectedStream, [$attachment]);

        $download = $this->attachmentService->streamAttachment($attachmentId, true);
        $this->assertEquals(new Download(new ReadableStreamContentSource($expectedStream, "text/pdf"), "test.pdf"), $download);

    }


}