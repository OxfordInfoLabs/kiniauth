<?php


namespace Kiniauth\Test\Services\Attachment\Storage;


use Kiniauth\Objects\Attachment\Attachment;
use Kiniauth\Objects\Attachment\AttachmentSummary;
use Kiniauth\Services\Attachment\Storage\DatabaseAttachmentStorage;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class DatabaseAttachmentStorageTest extends TestCase {

    public function testSaveStreamAndDeleteOperateDirectlyOnAttachmentObject() {

        $databaseAttachmentStorage = new DatabaseAttachmentStorage();
        $attachment = new Attachment(new AttachmentSummary("hello.pdf", "application/pdf", "Contact", 11, "database"));

        $databaseAttachmentStorage->saveAttachmentContent($attachment, new ReadOnlyStringStream("Hello World"));

        // Check content updated
        $this->assertEquals("Hello World", $attachment->getContent());

        // Check can get content as string
        $this->assertEquals("Hello World", $databaseAttachmentStorage->getAttachmentContent($attachment));

        // Check can get content as stream
        $this->assertEquals(new ReadOnlyStringStream("Hello World"), $databaseAttachmentStorage->streamAttachmentContent($attachment));

        $databaseAttachmentStorage->removeAttachmentContent($attachment);
        $this->assertEquals(null, $attachment->getContent());
    }

}