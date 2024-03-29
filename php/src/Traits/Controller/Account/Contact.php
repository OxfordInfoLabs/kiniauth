<?php

namespace Kiniauth\Traits\Controller\Account;

use Kiniauth\Objects\Account\Account;

trait Contact {


    private $contactService;

    /**
     * Account constructor.
     * @param \Kiniauth\Services\Account\ContactService $contactService
     */
    public function __construct($contactService) {
        $this->contactService = $contactService;
    }

    /**
     * Get a contact by id, or a blank one
     *
     * @http GET /$contactId
     *
     * @param null $contactId
     * @return \Kiniauth\Objects\Account\Contact|mixed
     */
    public function getContact($contactId = null) {
        return $this->contactService->getContact($contactId);
    }

    /**
     * Save a Contact object
     *
     * @http POST /save
     *
     * @param \Kiniauth\Objects\Account\Contact $contact
     * @return \Kiniauth\Objects\Account\Contact
     */
    public function saveContact($contact, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $contact->setAccountId($accountId);
        return $this->contactService->saveContact($contact);
    }

    /**
     * Delete a contact from the system
     *
     * @http GET /delete
     *
     * @param $contactId
     */
    public function removeContact($contactId) {
        $this->contactService->deleteContact($contactId);
    }

    /**
     * Make the passed in contact the default
     *
     * @http GET /default
     *
     * @param $contactId
     * @return \Kiniauth\Objects\Account\Contact
     */
    public function setDefaultContact($contactId) {
        return $this->contactService->setDefaultContact($contactId);
    }

    /**
     * Get the contacts for the logged in account
     *
     * @http GET /contacts
     *
     * @param string $type
     *
     * @return mixed
     */
    public function getContacts($type = null) {
        return $this->contactService->getContacts($type);
    }
}
