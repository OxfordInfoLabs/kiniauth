<?php


namespace Kiniauth\Services\Communication\Email\Provider;


use Kinikit\Core\Configuration;

/**
 * Base email transport class.  Only one method required to be implemented (send)
 */
abstract class EmailProvider {

    const PROVIDER_PHP_MAILER = "PHP_MAILER";
    const PROVIDER_NULL = "NULL";

    /**
     * Send an email.
     *
     * @param $email
     * @return \Kiniauth\Objects\Communication\Email\EmailSendResult
     *
     */
    public abstract function send($email);


    /**
     * Get the active transport in use
     */
    public static function getProvider($providerKey) {

        switch ($providerKey) {
            case self::PROVIDER_PHP_MAILER:
                return new PHPMailerProvider();
                break;
            default:
                return new NullProvider();

        }

    }

}
