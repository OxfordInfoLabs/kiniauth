<?php


namespace Kiniauth\Services\Application;

use Kiniauth\Objects\Account\Account;

use Kiniauth\Objects\Security\User;


/**
 * Kiniauth session object.  This subclasses the Kinikit HTTP Session object to provide
 * nice access to the core session items required for Kiniauth.
 *
 * Class Session
 * @package Kiniauth\Objects\Application
 * @noProxy
 */
class Session implements \Kinikit\MVC\Session\Session {

    /**
     * @var \Kinikit\MVC\Session\Session
     */
    private $coreSession;


    /**
     * Session constructor.
     * @param \Kinikit\MVC\Session\Session $coreSession
     */
    public function __construct($coreSession) {
        $this->coreSession = $coreSession;
    }

    /**
     * Get the logged in user
     *
     * @return User
     */
    public function __getLoggedInUser() {
        return $this->getValue("loggedInUser");
    }


    /**
     * Set the logged in user
     *
     * @param $user User
     */
    public function __setLoggedInUser($user) {
        $this->setValue("loggedInUser", $user);
    }

    /**
     * Get the pending logged in user
     *
     * @return User
     */
    public function __getPendingLoggedInUser() {
        return $this->getValue("pendingLoggedInUser");
    }


    /**
     * Set the pending logged in user
     *
     * @param $user User
     */
    public function __setPendingLoggedInUser($user) {
        $this->setValue("pendingLoggedInUser", $user);
    }

    /**
     * Get the logged in account
     *
     * @return Account
     */
    public function __getLoggedInAccount() {
        return $this->getValue("loggedInAccount");
    }


    /**
     * Set the logged in account
     *
     * @param $account
     */
    public function __setLoggedInAccount($account) {
        $this->setValue("loggedInAccount", $account);
    }


    /**
     * Get logged in privileges array - keyed in by account id.  Cached for performance.
     */
    public function __getLoggedInPrivileges() {
        return $this->getValue("loggedInPrivileges");
    }


    /**
     * Set logged in privileges array - keyed in by account id.  Cached here for performance.
     *
     * @param $privileges
     */
    public function __setLoggedInPrivileges($privileges) {
        $this->setValue("loggedInPrivileges", $privileges);
    }


    /**
     * Get the referring URL
     *
     * @return $string
     */
    public function __getReferringURL() {
        return $this->getValue("referringURL");
    }


    /**
     * Set the referring URL.
     *
     * @param $referringURL
     */
    public function __setReferringURL($referringURL) {
        $this->setValue("referringURL", $referringURL);
    }


    /**
     * Get the active parent account Id.
     *
     * @return integer
     */
    public function __getActiveParentAccountId() {
        return $this->getValue("activeParentAccountId");
    }


    /**
     * Set the active parent account Id.
     *
     * @param $activeParentAccountId
     */
    public function __setActiveParentAccountId($activeParentAccountId) {
        $this->setValue("activeParentAccountId", $activeParentAccountId);
    }


    /**
     * Set a session value for a string key.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setValue($key, $value) {
        $this->coreSession->setValue($key, $value);
    }

    /**
     * Get a session value by key
     *
     * @param string $key
     * @return mixed
     */
    public function getValue($key) {
        return $this->coreSession->getValue($key);
    }

    /**
     * Get all values - return as array of values keyed in by string.
     *
     * @return mixed[string]
     */
    public function getAllValues() {
        return $this->coreSession->getAllValues();
    }

    /**
     * Clear the session of all values
     *
     */
    public function clearAll() {
        return $this->coreSession->clearAll();
    }

    /**
     * Reload session data - particularly useful if session implementation is caching
     *
     * @return mixed
     */
    public function reload() {
        return $this->coreSession->reload();
    }
}
