<?php

namespace Kiniauth\Controllers\Admin;

use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\ValueObjects\Communication\StoredEmailSummaryItem;

class Communication {

    /**
     * @var EmailService
     */
    private $emailService;


    /**
     * @param EmailService $emailService
     */
    public function __construct($emailService) {
        $this->emailService = $emailService;
    }

    /**
     * @http POST /email/filter
     *
     * @param $filters
     * @return StoredEmailSummaryItem[]
     */
    public function filterStoredEmails($filters, $limit = 10, $offset = 0) {
        return array_map(function ($storedEmailSummary) {
            return StoredEmailSummaryItem::fromStoredEmailSummary($storedEmailSummary);
        }, $this->emailService->filterStoredEmails($filters, $limit, $offset));
    }


    /**
     * @http GET /email/$id
     *
     * @param integer $id
     * @return string
     */
    public function getStoredEmailContents($id) {
        return $this->emailService->getStoredEmail($id)->getTextBody();
    }

}