<?php


namespace Kiniauth\Services\Account;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Account\Contact;

class ContactService {

    /**
     * @param null $contactId
     * @return Contact|mixed
     */
    public function getContact($contactId = null) {
        try {
            return Contact::fetch($contactId);
        } catch (\Exception $e) {
            return new Contact();
        }
    }

    /**
     * @param Contact $contact
     * @return Contact
     */
    public function saveContact($contact) {
        $contact->save();
        return $contact;
    }

    public function deleteContact($contactId) {
        Contact::fetch($contactId);

    }

    /**
     * Return the contacts for the logged in account
     *
     * @param string $accountId
     * @return mixed
     */
    public function getContacts($accountId = Account::LOGGED_IN_ACCOUNT) {
        return Contact::filter("WHERE accountId = {$accountId}");
    }
}
