<?php


namespace Kiniauth\Services\Communication\Email;

use Kiniauth\Objects\Communication\Email\Email;
use Kiniauth\Objects\Communication\Email\EmailSendResult;
use Kiniauth\Services\Communication\Email\Provider\EmailProvider;
use Kinikit\Core\Configuration;

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
     * @return EmailSendResult
     */
    public function send($email) {

        // Send the email
        $response = $this->provider->send($email);

        $email->setStatus($response->getStatus());

        if ($response->getStatus() == Email::STATUS_SENT) {
            $email->save();
            $response = new EmailSendResult(Email::STATUS_SENT, null, $email->getId());
        } else {
            $email->setErrorMessage($response->getErrorMessage());
            $email->save();
        }

        return $response;
    }


}
