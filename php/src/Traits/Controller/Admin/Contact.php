<?php

namespace Kiniauth\Traits\Controller\Admin;

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
     * @http GET /
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
    public function saveContact($contact) {
        return $this->contactService->saveContact($contact);
    }
}
