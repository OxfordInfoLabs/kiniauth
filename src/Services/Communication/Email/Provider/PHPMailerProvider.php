<?php


namespace Kiniauth\Services\Communication\Email\Provider;

use Kiniauth\Objects\Communication\Email\Email;
use Kiniauth\Objects\Communication\Email\EmailSendResult;
use Kinikit\Core\Configuration;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Main email provider using PHP mail to send emails
 *
 * @package Kiniauth\Objects\Communication\Email\Provider
 */
class PHPMailerProvider implements EmailProvider {

    /**
     * The SMTP host to connect to.
     *
     * @var string
     */
    private $smtpHost;

    /**
     * The SMTP port to connect to
     *
     * @var integer
     */
    private $smtpPort;

    /**
     * The SMTP Username
     *
     * @var string
     */
    private $smtpUsername;

    /**
     * The SMTP password
     *
     * @var string
     */
    private $smtpPassword;

    /**
     * The SMTP security mode (one of the SMTP constants below).
     *
     * @var string
     */
    private $smtpSecurity;

    /**
     * Tunnel user for testing purposes.  All email is redirected to this user.
     *
     * @var string
     */
    private $tunnelUser;


    const SMTP_SECURITY_TLS = 'tls';
    const SMTP_SECURITY_SSL = 'ssl';

    /**
     * Constructor for PHP mailer
     */
    public function __construct($smtpHost = null, $smtpPort = null, $smtpUsername = null,
                                $smtpPassword = null, $smtpSecurity = null, $tunnelUser = null) {

        $this->smtpHost = $smtpHost ? $smtpHost : Configuration::readParameter("email.smtp.host");
        $this->smtpPort = $smtpPort ? $smtpPort : Configuration::readParameter("email.smtp.port");
        $this->smtpUsername = $smtpUsername ? $smtpUsername : Configuration::readParameter("email.smtp.username");
        $this->smtpPassword = $smtpPassword ? $smtpPassword : Configuration::readParameter("email.smtp.password");
        $this->smtpSecurity = $smtpSecurity ? $smtpSecurity : Configuration::readParameter("email.smtp.security");
        $this->tunnelUser = $tunnelUser ? $tunnelUser : Configuration::readParameter("email.tunneluser");


    }


    /**
     * Send an email.
     *
     * @param Email $email
     *
     * @return \Kiniauth\Objects\Communication\Email\EmailSendResult
     *
     */
    public function send($email) {

        $phpMailer = new PHPMailer(true);

        try {

            // Do the config of SMTP Stuff first
            if ($this->smtpHost)
                $phpMailer->Host = $this->smtpHost;

            if ($this->smtpPort)
                $phpMailer->Port = $this->smtpPort;

            if ($this->smtpUsername) {
                $phpMailer->SMTPAuth = true;
                $phpMailer->Username = $this->smtpUsername;
            }

            if ($this->smtpPassword) {
                $phpMailer->Password = $this->smtpPassword;
            }

            if ($this->smtpSecurity) {
                $phpMailer->SMTPSecure = $this->smtpSecurity;
            }


            // Now set up recipients
            $from = $this->convertAddressToAddressAndName($email->getSender())[0];
            $phpMailer->setFrom($from[0], $from[1]);

            if ($this->tunnelUser) {
                $recipient = $this->convertAddressToAddressAndName($this->tunnelUser)[0];
                $phpMailer->addAddress($recipient[0], $recipient[1]);
            } else {
                $recipients = $this->convertAddressToAddressAndName($email->getRecipients());
                foreach ($recipients as $recipient) {

                    $phpMailer->addAddress($recipient[0], $recipient[1]);
                }
            }

            if ($email->getReplyTo()) {
                $replyTo = $this->convertAddressToAddressAndName($email->getReplyTo() ? $email->getReplyTo() : $email->getSender())[0];
                $phpMailer->addReplyTo($replyTo[0], $replyTo[1]);
            }

            if ($email->getCc()) {
                $ccs = $this->convertAddressToAddressAndName($email->getCc());
                foreach ($ccs as $cc) {
                    $phpMailer->addCC($cc[0], $cc[1]);
                }

            }

            if ($email->getBcc()) {
                $bccs = $this->convertAddressToAddressAndName($email->getBcc());
                foreach ($bccs as $bcc) {
                    $phpMailer->addBCC($bcc[0], $bcc[1]);
                }

            }


            // Now set up content
            $phpMailer->isHTML(true);
            $phpMailer->Subject = $email->getSubject();
            $phpMailer->Body = $email->getTextBody();


            // Finally manage attachments
            foreach ($email->getLocalAttachmentFiles() as $attachmentFile) {
                $phpMailer->addAttachment($attachmentFile);
            }


            $phpMailer->send();

            return new EmailSendResult(Email::STATUS_SENT);

        } catch (Exception $e) {
            return new EmailSendResult(Email::STATUS_FAILED, $phpMailer->ErrorInfo);
        }
    }


    private function convertAddressToAddressAndName($address) {

        if (!is_array($address)) {
            $address = array($address);
        }

        $addressAndNames = array();
        foreach ($address as $addressComponent) {
            preg_match("/<(.*)>/", $addressComponent, $matches);
            if (sizeof($matches) == 2) {
                $addressAndNames[] = array($matches[1], trim(str_replace($matches[0], "", $addressComponent)));
            } else {
                $addressAndNames[] = array($matches[1], null);
            }
        }

        return $addressAndNames;

    }
}
