<?php


namespace Kiniauth\Services\Application;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Security\Privilege;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserSummary;
use Kiniauth\Services\Security\SecurityService;

/**
 * Simple container containing common session data for return back to the
 * application for display purposes etc.
 *
 * @package Kiniauth\Services\Application
 * @noGenerate
 */
class SessionData  {

    /**
     * @var User
     */
    private $user;

    /**
     * @var AccountSummary
     */
    private $account;


    private $privileges;

    /**
     * Get session data using user and account objects to seed the data.
     *
     * SessionData constructor.
     * @param SecurityService $securityService
     * @param Session $session
     */
    public function __construct($securityService, $session) {
        /**
         * @var $user User
         * @var $account Account
         */
        list ($user, $account)  = $securityService->getLoggedInUserAndAccount();

        if ($user) {
            $this->user = $user->generateSummary();
        }
        if ($account) {
            $this->account = $account->generateSummary();
        }

        if ($user || $account) {
            $this->privileges = $session->__getLoggedInPrivileges();
        }
    }

    /**
     * @return UserSummary
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @return AccountSummary
     */
    public function getAccount() {
        return $this->account;
    }

    /**
     * @return array|Privilege
     */
    public function getPrivileges() {
        return $this->privileges;
    }


}
