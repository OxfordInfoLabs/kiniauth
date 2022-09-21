<?php

namespace Kiniauth\Test\Services\Communication\Email;

use Kiniauth\Objects\Attachment\Attachment;
use Kiniauth\Objects\Communication\Email\StoredEmail;
use Kiniauth\Objects\Communication\Email\StoredEmailSendResult;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\Communication\Email\Attachment\FileEmailAttachment;
use Kinikit\Core\Communication\Email\Email;
use Kinikit\Core\Communication\Email\EmailSendResult;
use Kinikit\Core\DependencyInjection\Container;

include_once __DIR__ . "/../../../autoloader.php";

class EmailServiceTest extends TestBase {

    /**
     * @var EmailService
     */
    private $emailService;


    public function setUp(): void {
        parent::setUp();

        $authenticationService = Container::instance()->get(AuthenticationService::class);
        $authenticationService->login("sam@samdavisdesign.co.uk", AuthenticationHelper::encryptPasswordForLogin("passwordsam@samdavisdesign.co.uk"));

        $this->emailService = Container::instance()->get(EmailService::class);
    }

    public function testWhenEmailSentCorrectlyWithDefaultProviderEmailIsAlsoLoggedInDatabase() {

        $email = new Email("mark@oxil.co.uk", ["test@joebloggs.com", "test2@home.com"], "Test Message", "Hello Joe, this is clearly a test",
            ["jane@test.com", "the@world.co.uk"], ["mary@test.com", "badger@haslanded.org"], "info@oxil.co.uk", 1);


        $result = $this->emailService->send($email, 1);

        $this->assertEquals(StoredEmailSendResult::STATUS_SENT, $result->getStatus());
        $this->assertNotNull($result->getEmailId());

        /**
         * @var Email $email
         */
        $email = StoredEmail::fetch($result->getEmailId());
        $this->assertEquals("mark@oxil.co.uk", $email->getSender());
        $this->assertEquals(["test@joebloggs.com", "test2@home.com"], $email->getRecipients());
        $this->assertEquals("Test Message", $email->getSubject());
        $this->assertEquals("Hello Joe, this is clearly a test", $email->getTextBody());
        $this->assertEquals(["jane@test.com", "the@world.co.uk"], $email->getCc());
        $this->assertEquals(["mary@test.com", "badger@haslanded.org"], $email->getBcc());
        $this->assertEquals("info@oxil.co.uk", $email->getReplyTo());
        $this->assertEquals(1, $email->getAccountId());

    }


    /**
     *
     */
    public function testWhenEmailSentWithAttachmentsTheyAreCorrectlyStoredInTheAttachmentTableAsWell() {

        $email = new Email("mark@oxil.co.uk", ["test@joebloggs.com", "test3@home.com"], "Test Message", "Hello Joe, this is clearly a test",
            ["jane@test.com", "the@world.co.uk"], ["mary@test.com", "badger@haslanded.org"], "info@oxil.co.uk", 1);

        $email->setAttachments([new FileEmailAttachment(__DIR__ . "/Provider/testimage.png"), new FileEmailAttachment(__DIR__ . "/Provider/testtext.txt")]);

        $result = $this->emailService->send($email, 1);

        $this->assertEquals(EmailSendResult::STATUS_SENT, $result->getStatus());
        $this->assertNotNull($result->getEmailId());

        /**
         * @var Email $email
         */
        $email = StoredEmail::fetch($result->getEmailId());
        $this->assertEquals("mark@oxil.co.uk", $email->getSender());
        $this->assertEquals(["test@joebloggs.com", "test3@home.com"], $email->getRecipients());
        $this->assertEquals("Test Message", $email->getSubject());
        $this->assertEquals("Hello Joe, this is clearly a test", $email->getTextBody());
        $this->assertEquals(["jane@test.com", "the@world.co.uk"], $email->getCc());
        $this->assertEquals(["mary@test.com", "badger@haslanded.org"], $email->getBcc());
        $this->assertEquals("info@oxil.co.uk", $email->getReplyTo());
        $this->assertEquals(1, $email->getAccountId());

        $this->assertEquals(2, sizeof($email->getAttachments()));

        $attachment1 = $email->getAttachments()[0];
        $this->assertEquals("testimage.png", $attachment1->getAttachmentFilename());
        $this->assertEquals("image/png", $attachment1->getMimeType());
        $this->assertEquals(1, $attachment1->getAccountId());


        $attachment2 = $email->getAttachments()[1];
        $this->assertEquals("testtext.txt", $attachment2->getAttachmentFilename());
        $this->assertEquals("text/plain", $attachment2->getMimeType());
        $this->assertEquals(1, $attachment2->getAccountId());

        $fullAttach1 = Attachment::fetch($attachment1->getId());
        $this->assertEquals(file_get_contents(__DIR__ . "/Provider/testimage.png"), $fullAttach1->getContent());

        $fullAttach2 = Attachment::fetch($attachment2->getId());
        $this->assertEquals(file_get_contents(__DIR__ . "/Provider/testtext.txt"), $fullAttach2->getContent());


    }


    public function testEmailSentWithSameHashAsPreviousEmailIsNotSentAndMarkedAsDuplicateUnlessSendDuplicateBooleanSupplied() {

        $email = new Email("mark@oxil.co.uk", ["test@joebloggs.com", "test4@home.com"], "Test Message", "Hello Joe, this is clearly a test",
            ["jane@test.com", "the@world.co.uk"], ["mary@test.com", "badger@haslanded.org"], "info@oxil.co.uk", 1);


        $result = $this->emailService->send($email, 1);
        $this->assertEquals(StoredEmailSendResult::STATUS_SENT, $result->getStatus());
        $this->assertNotNull($result->getEmailId());

        $duplicateResult = $this->emailService->send($email, 1);
        $this->assertEquals(StoredEmail::STATUS_DUPLICATE, $duplicateResult->getStatus());
        $this->assertNull($duplicateResult->getEmailId());

        $overrideResult = $this->emailService->send($email, 1, null, true);
        $this->assertEquals(StoredEmailSendResult::STATUS_SENT, $overrideResult->getStatus());
        $this->assertNotNull($overrideResult->getEmailId());
    }


}
