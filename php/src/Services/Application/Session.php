<?php


namespace Kiniauth\Services\Application;

use Kiniauth\Objects\Account\Account;

use Kiniauth\Objects\Security\Securable;
use Kiniauth\Objects\Security\User;
use Kinikit\Core\Util\StringUtils;


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
     *
     * @param \Kinikit\MVC\Session\Session $coreSession
     */
    public function __construct($coreSession) {
        $this->coreSession = $coreSession;
    }

    /**
     * Get the logged in securable
     *
     * @return Securable
     */
    public function __getLoggedInSecurable() {
        return $this->getValue("loggedInSecurable");
    }


    /**
     * Set the logged in securable
     *
     * @param $securable Securable
     */
    public function __setLoggedInSecurable($securable) {
        $this->setValue("loggedInSecurable", $securable);
    }


    /**
     * Get the logged in user access token hash for optimisation if
     * used.
     */
    public function __getLoggedInUserAccessTokenHash() {
        return $this->getValue("loggedInUserAccessTokenHash");
    }


    /**
     * Set the logged in user access token hash.
     *
     * @param $tokenHash
     */
    public function __setLoggedInUserAccessTokenHash($tokenHash) {
        $this->setValue("loggedInUserAccessTokenHash", $tokenHash);
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
     * Get the valid referrer flag.
     *
     * @return boolean
     */
    public function __getValidReferrer() {
        return $this->getValue("validReferrer");
    }

    /**
     * Set thevalid referrer flag
     *
     * @param boolean $validReferrer
     */
    public function __setValidReferrer($validReferrer) {
        $this->setValue("validReferrer", $validReferrer);
    }


    /**
     * Get the active parent account Id - fall back to top level at this stage.
     *
     * @return integer
     */
    public function __getActiveParentAccountId() {
        return $this->getValue("activeParentAccountId") ?? 0;
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
     * Get the array of delayed captchas
     *
     * @return array|mixed
     */
    public function __getDelayedCaptchas() {
        return $this->getValue("delayedCaptchas") ?? [];
    }

    public function __getDelayedCaptcha($url) {
        $delayedCaptchas = $this->getValue("delayedCaptchas");
        return $delayedCaptchas[$url] ?? 0;
    }

    /**
     * Add a delayed captcha to the session
     *
     * @param $url
     * @param int $failures
     */
    public function __addDelayedCaptcha($url, $failures = 1) {

        $delayedCaptchas = $this->getValue("delayedCaptchas");
        if (!$delayedCaptchas) {
            $delayedCaptchas = [];
        }
        $delayedCaptchas[$url] = $failures;
        $this->setValue("delayedCaptchas", $delayedCaptchas);
    }

    /**
     * Remove a delayed captcha
     *
     * @param $url
     */
    public function __removeDelayedCaptcha($url) {

        $delayedCaptchas = $this->getValue("delayedCaptchas");
        if ($delayedCaptchas && isset($delayedCaptchas[$url])) {
            unset($delayedCaptchas[$url]);
        }
        $this->setValue("delayedCaptchas", $delayedCaptchas);
    }


    /**
     * Get CSRF Token
     *
     * @return mixed
     */
    public function __getCSRFToken() {
        return $this->getValue("CSRFToken");
    }


    /**
     * Set CSRF Token
     *
     * @param $csrfToken
     */
    public function __setCSRFToken($csrfToken) {
        $this->setValue("CSRFToken", $csrfToken);
    }

    public function __getSessionSalt() {
        if (!$this->getValue("sessionSalt")) {
            $this->setValue("sessionSalt", StringUtils::generateRandomString(22));
        }
        return $this->getValue("sessionSalt");
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


    /**
     * Regenerate a session - generally called in authentication
     * scenarios to prevent session fixation
     *
     * @return mixed
     */
    public function regenerate() {
        return $this->coreSession->regenerate();
    }


    /**
     * Get the current session id
     *
     * @return mixed
     */
    public function getId() {
        return $this->coreSession->getId();
    }

    /**
     * Return a boolean determining whether or not the passed session is active
     *
     * @param $id
     * @return boolean
     */
    public function isActive($id) {
        return $this->coreSession->isActive($id);
    }


    /**
     * Destroy the session for the passed id.
     *
     * @param $id
     * @return mixed|void
     */
    public function destroy($id) {
        $this->coreSession->destroy($id);
    }

}
