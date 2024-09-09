<?php


namespace Kiniauth\Services\Account;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\Contact;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;

class ContactService {

    public function __construct(private DatabaseConnection $databaseConnection) {
    }

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

    /**
     * Remove a contact
     *
     * @param $contactId
     */
    public function deleteContact($contactId) {
        $contact = Contact::fetch($contactId);
        $contact->remove();
    }

    /**
     * Set the default contact for this account
     *
     * @param $contactId
     * @param string $accountId
     * @return Contact
     * @throws \Kinikit\Persistence\Database\Exception\SQLException
     */
    public function setDefaultContact($contactId, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $this->databaseConnection->execute("UPDATE ka_contact SET default_contact = 0 WHERE account_id = ? AND default_contact = 1", $accountId);
        /** @var Contact $contact */
        $contact = Contact::fetch($contactId);
        $contact->setDefaultContact(1);
        $contact->save();
        return $contact;
    }

    /**
     * Return the contacts for the logged in account
     *
     * @param string $type
     * @param string $accountId
     * @return mixed
     */
    public function getContacts($type = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        if ($type) {
            return Contact::filter("WHERE accountId = ? AND type = ?", $accountId, $type);
        } else {
            return Contact::filter("WHERE accountId = ?", $accountId);
        }

    }
}
