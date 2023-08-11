<?php

namespace Kiniauth\Controllers\Test;


use Kiniauth\Objects\Communication\Email\StoredEmail;

class Email {


    /**
     * @http GET /last
     * @objectInterceptorDisabled
     *
     * @return void
     */
    public function lastSentEmail() {
        $lastEmails = StoredEmail::filter("ORDER BY sent_date DESC LIMIT 1");
        return $lastEmails[0] ?? null;
    }

}