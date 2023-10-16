<?php


namespace Kiniauth\Services\Communication\Email;


use Kiniauth\Objects\Attachment\AttachmentSummary;
use Kiniauth\Objects\Communication\Email\StoredEmail;
use Kiniauth\Objects\Communication\Email\StoredEmailSendResult;
use Kiniauth\Services\Attachment\AttachmentService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kinikit\Core\Communication\Email\Email;
use Kinikit\Core\Communication\Email\Provider\EmailProvider;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;

/**
 * Service for sending and querying for sent emails.
 *
 */
class EmailService {

    /**
     * @var EmailProvider
     */
    private $provider;

    /**
     * @var AttachmentService
     */
    private $attachmentService;


    /**
     * Construct with the current provider.
     *
     * @param EmailProvider $provider
     * @param AttachmentService $attachmentService
     */
    public function __construct($provider, $attachmentService) {
        $this->provider = $provider;
        $this->attachmentService = $attachmentService;
    }


    /**
     * Send an ad-hoc email and log it in the database if successful.
     *
     * @param Email $email
     *
     * @return StoredEmailSendResult
     */
    public function send($email, $accountId = null, $userId = null, $sendDuplicates = false) {

        // If not sending duplicates, do a duplicate check
        if (!$sendDuplicates) {
            // Check for duplicate stored email first and quit if there is one
            $previousEmails = StoredEmail::values("COUNT(*)", "WHERE hash = ?", $email->getHash());
            if ($previousEmails[0] > 0) {
                return new StoredEmailSendResult(StoredEmail::STATUS_DUPLICATE);
            }
        }

        // Send the email
        $response = $this->provider->send($email);

        // Save the email
        $storedEmail = new StoredEmail($email, $accountId, $userId, $response->getStatus(), $response->getErrorMessage());

        $activeRecordInterceptor = Container::instance()->get(ActiveRecordInterceptor::class);

        $activeRecordInterceptor->executeInsecure(function () use ($storedEmail, $email, $accountId) {
            $storedEmail->save();

            if (is_array($email->getAttachments())) {
                foreach ($email->getAttachments() as $attachment) {
                    $attachmentSummary = new AttachmentSummary($attachment->getAttachmentFilename(), $attachment->getContentMimeType(), "Email", $storedEmail->getId(), null, null, $accountId);
                    $this->attachmentService->saveAttachment($attachmentSummary, new ReadOnlyStringStream($attachment->getContent()));
                }

            }

        });


        $response = new StoredEmailSendResult($response->getStatus(), $response->getErrorMessage(), $storedEmail->getId());

        return $response;
    }


}
