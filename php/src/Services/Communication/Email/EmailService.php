<?php


namespace Kiniauth\Services\Communication\Email;


use Kiniauth\Objects\Attachment\AttachmentSummary;
use Kiniauth\Objects\Communication\Email\StoredEmail;
use Kiniauth\Objects\Communication\Email\StoredEmailSendResult;
use Kiniauth\Objects\Communication\Email\StoredEmailSummary;
use Kiniauth\Services\Attachment\AttachmentService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kinikit\Core\Communication\Email\Email;
use Kinikit\Core\Communication\Email\Provider\EmailProvider;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinikit\Persistence\ORM\Query\Filter\LikeFilter;
use Kinikit\Persistence\ORM\Query\Query;

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


    /**
     * Filter stored emails either via a free search or a recipient specific filter.
     *
     * @param string[] $filters
     * @param int $limit
     * @param int $offset
     * @return StoredEmailSummary[]
     */
    public function filterStoredEmails($filters = [], $limit = 10, $offset = 0) {

        if (isset($filters["recipientAddress"])) {
            $filters["recipientAddress"] = new LikeFilter("recipients", "%" . $filters["recipientAddress"] . "%");
        }

        if (isset($filters["search"])) {
            $filters["search"] = new LikeFilter(["sender", "recipients", "cc", "bcc", "subject", "replyTo"], "%" . $filters["search"] . "%");
        }

        $query = new Query(StoredEmailSummary::class);
        return $query->query($filters, "id DESC", $limit, $offset);

    }

    /**
     * Get a full stored email by id
     *
     * @param $id
     * @return StoredEmail
     */
    public function getStoredEmail($id) {
        return StoredEmail::fetch($id);
    }


}
