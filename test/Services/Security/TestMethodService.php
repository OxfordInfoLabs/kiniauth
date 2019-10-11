<?php


namespace Kiniauth\Test\Services\Security;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\Contact;
use Kiniauth\Objects\Security\User;

/**
 * Class TestMethodService
 * @package Kiniauth\Test\Services\Security
 *
 *
 */
class TestMethodService {


    /**
     * Normal unrestricted method
     */
    public function normalMethod() {
        $contact = new Contact("Mark R", "Test Organisation", "My Lane", "My Shire", "Oxford",
            "Oxon", "OX4 7YY", "GB", null, "test@test.com", 1, Contact::ADDRESS_TYPE_GENERAL);


        $contact->save();
    }


    /**
     * Object interceptor disabled method
     *
     * @objectInterceptorDisabled
     */
    public function objectInterceptorDisabledMethod() {
        $contact = new Contact("Mark R", "Test Organisation", "My Lane", "My Shire", "Oxford",
            "Oxon", "OX4 7YY", "GB", null, "test@test.com", 1, Contact::ADDRESS_TYPE_GENERAL);
        $contact->save();
    }


    /**
     *
     * @hasPrivilege deletedata
     *
     * @return string
     */
    public function accountPermissionRestricted() {
        return "OK";
    }


    /**
     * @hasPrivilege editdata($accountId)
     *
     * @param $accountId
     * @param $newName
     */
    public function otherAccountPermissionRestricted($accountId, $newName) {
        return "DONE";
    }


    /**
     * Special magic logged in constant
     *
     * @param $param1
     * @param null $accountId
     */
    public function loggedInAccountInjection($param1, $accountId = Account::LOGGED_IN_ACCOUNT) {
        return array($param1, $accountId);
    }

    /**
     * Special magic logged in constant
     *
     * @param $param1
     * @param null $userId
     */
    public function loggedInUserInjection($param1, $userId = User::LOGGED_IN_USER) {
        return array($param1, $userId);
    }

}
