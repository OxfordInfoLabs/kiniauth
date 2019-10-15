<?php


namespace Kiniauth\Services\Communication\Email;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Attachment\Attachment;
use Kiniauth\Objects\Communication\Email\StoredEmail;
use Kiniauth\Objects\Communication\Email\StoredEmailSendResult;
use Kinikit\Core\Communication\Email\Email;
use Kinikit\Core\Communication\Email\EmailSendResult;
use Kinikit\Core\Communication\Email\Provider\EmailProvider;

/**
 * Service for sending and querying for sent emails.
 *
 */
class EmailService {

    private $provider;

    /**
     * Construct with the current provider.
     *
     * @param EmailProvider $provider
     */
    public function __construct($provider) {
        $this->provider = $provider;
    }


    /**
     * Send an ad-hoc email and log it in the database if successful.
     *
     * @param Email $email
     *
     * @return StoredEmailSendResult
     */
    public function send($email, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Send the email
        $response = $this->provider->send($email);

        // Save the email
        $storedEmail = new StoredEmail($email, $accountId, $response->getStatus(), $response->getErrorMessage());
        $storedEmail->save();

        if (is_array($email->getAttachments())) {
            foreach ($email->getAttachments() as $attachment) {
                $attachment = new Attachment("Email", $storedEmail->getId(), $attachment->getContent(), $attachment->getContentMimeType(), $attachment->getAttachmentFilename(), $accountId);
                $attachment->save();
            }

        }


        $response = new StoredEmailSendResult($response->getStatus(), $response->getErrorMessage(), $storedEmail->getId());

        return $response;
    }


}
