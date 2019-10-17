<?php


namespace Kiniauth\Services\Communication\Email;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Attachment\Attachment;
use Kiniauth\Objects\Communication\Email\StoredEmail;
use Kiniauth\Objects\Communication\Email\StoredEmailSendResult;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kinikit\Core\Communication\Email\Email;
use Kinikit\Core\Communication\Email\EmailSendResult;
use Kinikit\Core\Communication\Email\Provider\EmailProvider;

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
     * @var ActiveRecordInterceptor
     */
    private $activeRecordInterceptor;

    /**
     * Construct with the current provider.
     *
     * @param EmailProvider $provider
     * @param ActiveRecordInterceptor $activeRecordInterceptor
     */
    public function __construct($provider, $activeRecordInterceptor) {
        $this->provider = $provider;
        $this->activeRecordInterceptor = $activeRecordInterceptor;
    }


    /**
     * Send an ad-hoc email and log it in the database if successful.
     *
     * @param Email $email
     *
     * @return StoredEmailSendResult
     */
    public function send($email, $accountId = null, $userId = null) {

        // Send the email
        $response = $this->provider->send($email);

        // Save the email
        $storedEmail = new StoredEmail($email, $accountId, $userId, $response->getStatus(), $response->getErrorMessage());

        $this->activeRecordInterceptor->executeInsecure(function () use ($storedEmail, $email, $accountId) {
            $storedEmail->save();

            if (is_array($email->getAttachments())) {
                foreach ($email->getAttachments() as $attachment) {
                    $attachment = new Attachment("Email", $storedEmail->getId(), $attachment->getContent(), $attachment->getContentMimeType(), $attachment->getAttachmentFilename(), $accountId);
                    $attachment->save();
                }

            }

        });


        $response = new StoredEmailSendResult($response->getStatus(), $response->getErrorMessage(), $storedEmail->getId());

        return $response;
    }


}
