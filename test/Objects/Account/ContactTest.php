<?php


namespace Kiniauth\Test\Objects\Account;

use Kiniauth\Objects\Account\Contact;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;

include_once __DIR__ . "/../../autoloader.php";

class ContactTest extends TestBase {

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    public function setUp(): void {
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
    }

    public function testWhenNoDefaultContactForAccountFirstContactIsMarkedAsDefault() {

        // Log in as a user
        $this->authenticationService->login("simon@peterjonescarwash.com", "password");

        // Save a contact
        $contact = new Contact("Jeff Smith", "Jeff inc", "3 My house", "", "Oxford", "Oxon", "OX4 2RR", "GB");
        $contact->setAccountId(2);
        $contact->save();

        // Pull and check for default-ness
        $reContact = Contact::fetch($contact->getId());
        $this->assertTrue($reContact->isDefaultContact());

        $contact2 = new Contact("Jeff Miles", "Jeff inc", "3 My house", "", "Oxford", "Oxon", "OX4 2RR", "GB");
        $contact2->setAccountId(2);
        $contact2->save();

        $reContact2 = Contact::fetch($contact2->getId());
        $this->assertFalse($reContact2->isDefaultContact());

        // Delete the original contact.
        $reContact->remove();

        // Check second contact is now marked as default.
        $reContact2 = Contact::fetch($contact2->getId());
        $this->assertTrue($reContact2->isDefaultContact());


    }

}
