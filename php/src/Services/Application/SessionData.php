<?php


namespace Kiniauth\Services\Application;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Security\Privilege;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserSummary;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\Core\Configuration\Configuration;

/**
 * Simple container containing common session data for return back to the
 * application for display purposes etc.
 *
 * @package Kiniauth\Services\Application
 * @noGenerate
 */
class SessionData {

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
     * @var string[]
     */
    private $delayedCaptchas;

    /**
     * @var string
     */
    private $csrfToken;

    /**
     * @var string
     */
    private $sessionSalt;

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
        list ($user, $account) = $securityService->getLoggedInSecurableAndAccount();

        if ($user) {
            $this->user = $user->generateSummary();
        }
        if ($account) {
            $this->account = $account->generateSummary();
        }

        if ($user || $account) {
            $this->privileges = $session->__getLoggedInPrivileges();
        }

        $this->delayedCaptchas = $session->__getDelayedCaptchas();

        $this->csrfToken = $session->__getCSRFToken();

        $this->sessionSalt = $session->__getSessionSalt();
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

    /**
     * @return string[]
     */
    public function getDelayedCaptchas() {
        return $this->delayedCaptchas;
    }

    /**
     * @return string
     */
    public function getCsrfToken() {
        return $this->csrfToken;
    }

    /**
     * Client side boolean.
     *
     * @return int
     */
    public function getLoaded() {
        return 1;
    }

    /**
     * @return string
     */
    public function getSessionSalt() {
        return $this->sessionSalt;
    }

}
