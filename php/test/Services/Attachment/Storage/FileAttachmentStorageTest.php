<?php


namespace Kiniauth\Test\Services\Attachment\Storage;


use Kiniauth\Objects\Attachment\Attachment;
use Kiniauth\Objects\Attachment\AttachmentSummary;
use Kiniauth\Services\Attachment\Storage\FileAttachmentStorage;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class FileAttachmentStorageTest extends TestCase {

    public function tearDown(): void {
        if (file_exists("Files")) {
            passthru("rm -rf Files");
        }
    }

    public function testSaveStreamAndDeleteWriteToConfiguredFileAttachmentStorageDirectoryForAdminFiles() {

        Configuration::instance()->addParameter("file.attachment.storage.root", "Files");

        $fileAttachmentStorage = new FileAttachmentStorage();
        $attachment = new Attachment(new AttachmentSummary("hello.txt", "application/pdf", "Contact", 11, "file", null, null, 13));

        $fileAttachmentStorage->saveAttachmentContent($attachment, new ReadOnlyStringStream("Hello World"));

        // Check content updated
        $targetFilename = "Files/Admin/Contact/11/13-hello.txt";
        $this->assertTrue(file_exists($targetFilename));
        $this->assertEquals("Hello World", file_get_contents($targetFilename));

        // Check can get content as string
        $this->assertEquals("Hello World", $fileAttachmentStorage->getAttachmentContent($attachment));

        // Check can get content as stream
        $this->assertEquals("Hello World", $fileAttachmentStorage->streamAttachmentContent($attachment)->getContents());

        $fileAttachmentStorage->removeAttachmentContent($attachment);
        $this->assertFalse(file_exists($targetFilename));
    }

    public function testSaveStreamAndDeleteWriteToConfiguredFileAttachmentStorageDirectoryForAccountFiles() {

        Configuration::instance()->addParameter("file.attachment.storage.root", "Files");

        $fileAttachmentStorage = new FileAttachmentStorage();
        $attachment = new Attachment(new AttachmentSummary("hello.txt", "application/pdf", "Contact", 11, "file", null, 50, 13));

        $fileAttachmentStorage->saveAttachmentContent($attachment, new ReadOnlyStringStream("Hello World"));

        // Check content updated
        $targetFilename = "Files/Account/50/Contact/11/13-hello.txt";
        $this->assertTrue(file_exists($targetFilename));
        $this->assertEquals("Hello World", file_get_contents($targetFilename));

        // Check can get content as string
        $this->assertEquals("Hello World", $fileAttachmentStorage->getAttachmentContent($attachment));

        // Check can get content as stream
        $this->assertEquals("Hello World", $fileAttachmentStorage->streamAttachmentContent($attachment)->getContents());

        $fileAttachmentStorage->removeAttachmentContent($attachment);
        $this->assertFalse(file_exists($targetFilename));
    }


    public function testSaveStreamAndDeleteWriteToConfiguredFileAttachmentStorageDirectoryForAccountProjectFiles() {

        Configuration::instance()->addParameter("file.attachment.storage.root", "Files");

        $fileAttachmentStorage = new FileAttachmentStorage();
        $attachment = new Attachment(new AttachmentSummary("hello.txt", "application/pdf", "Contact", 11, "file", "mytestproject", 50, 13));

        $fileAttachmentStorage->saveAttachmentContent($attachment, new ReadOnlyStringStream("Hello World"));

        // Check content updated
        $targetFilename = "Files/Account/50/mytestproject/Contact/11/13-hello.txt";
        $this->assertTrue(file_exists($targetFilename));
        $this->assertEquals("Hello World", file_get_contents($targetFilename));

        // Check can get content as string
        $this->assertEquals("Hello World", $fileAttachmentStorage->getAttachmentContent($attachment));

        // Check can get content as stream
        $this->assertEquals("Hello World", $fileAttachmentStorage->streamAttachmentContent($attachment)->getContents());

        $fileAttachmentStorage->removeAttachmentContent($attachment);
        $this->assertFalse(file_exists($targetFilename));
    }


}