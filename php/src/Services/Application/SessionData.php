<?php


namespace Kiniauth\Services\Application;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Security\Privilege;
use Kiniauth\Objects\Security\Securable;
use Kiniauth\Services\Security\SecurityService;

/**
 * Simple container containing common session data for return back to the
 * application for display purposes etc.
 *
 * @package Kiniauth\Services\Application
 * @noGenerate
 */
class SessionData {

    /**
     * @var Securable
     */
    private $securable;

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
     * Get session data using securable and account objects to seed the data.
     *
     * SessionData constructor.
     * @param SecurityService $securityService
     * @param Session $session
     */
    public function __construct($securityService, $session) {
        /**
         * @var $securable Securable
         * @var $account Account
         */
        list ($securable, $account) = $securityService->getLoggedInSecurableAndAccount();

        if ($securable) {
            $this->securable = $securable->generateSummary();
        }
        if ($account) {
            $this->account = $account->generateSummary();
        }

        if ($securable || $account) {
            $this->privileges = $session->__getLoggedInPrivileges();
        }

        $this->delayedCaptchas = $session->__getDelayedCaptchas();

        $this->csrfToken = $session->__getCSRFToken();

        $this->sessionSalt = $session->__getSessionSalt();
    }

    /**
     * @return Securable
     */
    public function getSecurable() {
        return $this->securable;
    }

    /**
     * @return Securable
     */
    public function getUser() {
        return $this->getSecurable();
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
