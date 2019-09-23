<?php


namespace Kiniauth\Services\Communication\Email\Provider;

use Kiniauth\Objects\Communication\Email\Email;
use Kiniauth\Objects\Communication\Email\EmailSendResult;

/**
 * Null provider which does nothing - default for testing and development.
 *
 * @package Kiniauth\Objects\Communication\Email\Transport
 */
class NullProvider extends EmailProvider {

    /**
     * Send an email.
     *
     * @param Email $email
     *
     * @return \Kiniauth\Objects\Communication\Email\EmailSendResult
     *
     */
    public function send($email) {
        return new EmailSendResult(Email::STATUS_SENT);
    }
}
