<?php


namespace Kiniauth\Objects\Account;


use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;

/**
 * Class ContactInterceptor
 * @package Kiniauth\Objects\Account
 */
class ContactInterceptor extends DefaultORMInterceptor {

    public function __construct(
        private DatabaseConnection $databaseConnection
    ) {
    }

    /**
     * Check for account default
     *
     * @param $object
     */
    public function postSave($object) {
        $this->ensureDefaultContact($object->getAccountId());
    }

    /**
     * Check for account default
     *
     * @param $object
     */
    public function postDelete($object) {
        $this->ensureDefaultContact($object->getAccountId());
    }

    // Check for default contact
    private function ensureDefaultContact($accountId) {
        $results = $this->databaseConnection->query("SELECT COUNT(*) total FROM ka_contact WHERE account_id = ? AND default_contact = 1", $accountId);
        $total = $results->fetchAll()[0]["total"];
        if ($total == 0) {
            $firstContact = Contact::filter("WHERE account_id = ? LIMIT 1", $accountId);
            if (sizeof($firstContact) > 0) {
                $firstContact[0]->setDefaultContact(true);
                $firstContact[0]->save();
            }
        }
    }


}
