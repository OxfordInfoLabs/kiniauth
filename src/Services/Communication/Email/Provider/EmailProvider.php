<?php


namespace Kiniauth\Services\Communication\Email\Provider;


use Kinikit\Core\Configuration;

/**
 * Base email transport class.  Only one method required to be implemented (send)
 *
 * @implementationConfigParam email.provider
 * @implementation php \Kiniauth\Services\Communication\Email\Provider\PHPMailerProvider
 * @defaultImplementation \Kiniauth\Services\Communication\Email\Provider\NullProvider
 *
 */
interface EmailProvider {

    /**
     * Send an email.
     *
     * @param $email
     * @return \Kiniauth\Objects\Communication\Email\EmailSendResult
     *
     */
    public function send($email);


}
