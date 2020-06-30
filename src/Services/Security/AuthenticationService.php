<?php


namespace Kiniauth\Services\Security;


use Kiniauth\Exception\Security\InvalidAPICredentialsException;
use Kiniauth\Exception\Security\InvalidLoginException;
use Kiniauth\Exception\Security\InvalidReferrerException;
use Kiniauth\Exception\Security\InvalidUserAccessTokenException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserAccessToken;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\TwoFactor\TwoFactorProvider;
use Kiniauth\Services\Workflow\PendingActionService;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Security\Hash\HashProvider;
use Kinikit\Core\Security\Hash\SHA512HashProvider;
use Kinikit\MVC\Request\URL;


/**
 * AuthenticationService object for coordinating authentication functions for Kiniauth.
 *
 * Class AuthenticationService
 * @package Kiniauth\Workers\Application
 */
class AuthenticationService {

    private $settingsService;
    private $session;
    private $securityService;
    private $twoFactorProvider;
    private $userService;


    /**
     * @var HashProvider
     */
    private $hashProvider;


    /**
     * @var UserSessionService
     */
    private $userSessionService;

    const STATUS_LOGGED_IN = "LOGGED_IN";
    const STATUS_REQUIRES_2FA = "REQUIRES_2FA";
    const STATUS_ACTIVE_SESSION = "ACTIVE_SESSION";

    /**
     * @param \Kiniauth\Services\Application\SettingsService $settingsService
     * @param \Kiniauth\Services\Application\Session $session
     * @param \Kiniauth\Services\Security\SecurityService $securityService
     * @param TwoFactorProvider $twoFactorProvider
     * @param HashProvider $hashProvider
     * @param UserService $userService
     * @param UserSessionService $userSessionService
     */
    public function __construct($settingsService, $session, $securityService, $twoFactorProvider, $hashProvider, $userService, $userSessionService) {
        $this->settingsService = $settingsService;
        $this->session = $session;
        $this->securityService = $securityService;
        $this->twoFactorProvider = $twoFactorProvider;
        $this->hashProvider = $hashProvider;
        $this->userService = $userService;
        $this->userSessionService = $userSessionService;
    }

    /**
     * Boolean indicator as to whether or not an email address exists.
     *
     * @param $emailAddress
     * @param null $contextKey
     */
    public function emailExists($emailAddress, $parentAccountId = null) {

        if ($parentAccountId === null) {
            $parentAccountId = $this->session->__getActiveParentAccountId() ? $this->session->__getActiveParentAccountId() : 0;
        }

        return User::values("COUNT(*)", "WHERE emailAddress = ? AND parentAccountId = ?", $emailAddress, $parentAccountId)[0] > 0;
    }


    /**
     * Log in with an email address and password.
     *
     * @param $emailAddress
     * @param $password
     *
     * @objectInterceptorDisabled
     */
    public function login($emailAddress, $password, $parentAccountId = null) {

        if ($parentAccountId === null) {
            $parentAccountId = $this->session->__getActiveParentAccountId() ? $this->session->__getActiveParentAccountId() : 0;
        }

        $matchingUsers = User::filter("WHERE emailAddress = ? AND parentAccountId = ?", $emailAddress, $parentAccountId);

        // If there is a matching user, return it now.
        if (sizeof($matchingUsers) > 0) {
            /** @var User $user */
            $user = $matchingUsers[0];

            if ($user->passwordMatches($password, $this->session->__getSessionSalt())) {

                // If we are single sessioning, ensure we
                if (Configuration::readParameter("login.single.session")) {
                    $otherSessions = $this->userSessionService->listAuthenticatedSessions($user->getId());

                    if (sizeof($otherSessions) > 0) {
                        $this->session->__setPendingLoggedInUser($user);
                        return self::STATUS_ACTIVE_SESSION;
                    }
                }

                if ($user->getTwoFactorData()) {
                    $this->session->__setPendingLoggedInUser($user);
                    return self::STATUS_REQUIRES_2FA;
                } else {
                    $this->securityService->logIn($user);
                    return self::STATUS_LOGGED_IN;
                }
            } else {

                // Invalid password
                if ($user->getStatus() == User::STATUS_ACTIVE && $maxLoginAttempts = Configuration::readParameter("login.max.attempts")) {


                    $existingLoginAttempts = $user->getInvalidLoginAttempts();
                    $user->setInvalidLoginAttempts($existingLoginAttempts + 1);

                    // Lock the user if we have exceeded max login attempts
                    if ($existingLoginAttempts >= $maxLoginAttempts) {
                        $this->userService->lockUser($user->getId());
                    } else {
                        $user->save();
                    }
                }

                throw new InvalidLoginException();
            }
        } else {
            // Invalid username
            throw new InvalidLoginException();
        }
    }

    /**
     * Close any active sessions and continue with the login process - this should be called
     * after a STATUS_ACTIVE_SESSION is returned from login method.
     *
     * @objectInterceptorDisabled
     */
    public function closeActiveSessionsAndLogin() {
        if ($pendingUser = $this->session->__getPendingLoggedInUser()) {

            // Read and terminate all authenticated sessions
            $activeSessions = $this->userSessionService->listAuthenticatedSessions($pendingUser->getId());
            foreach ($activeSessions as $activeSession) {
                $this->userSessionService->terminateAuthenticatedSession($pendingUser->getId(), $activeSession->getSessionId());
            }

            if ($pendingUser->getTwoFactorData()) {
                $this->session->__setPendingLoggedInUser($pendingUser);
                return self::STATUS_REQUIRES_2FA;
            } else {
                $this->session->__setPendingLoggedInUser(null);
                $this->securityService->logIn($pendingUser);
                return self::STATUS_LOGGED_IN;
            }

        } else {
            throw new InvalidLoginException("No pending login");
        }
    }

    /**
     * Check the supplied two factor code and authenticate the login if correct.
     *
     * @param $code
     * @return bool
     * @throws InvalidLoginException
     * @throws \Kiniauth\Exception\Security\AccountSuspendedException
     * @throws \Kiniauth\Exception\Security\UserSuspendedException
     *
     * @objectInterceptorDisabled
     */
    public function authenticateTwoFactor($code) {

        $pendingUser = $this->session->__getPendingLoggedInUser();

        if (Configuration::readParameter("login.single.session")) {
            $otherSessions = $this->userSessionService->listAuthenticatedSessions($pendingUser->getId());
            if (sizeof($otherSessions) > 0) {
                throw new InvalidLoginException("Active session exists");
            }
        }

        if (strlen($code) === 6) {

            $secretKey = $pendingUser->getTwoFactorData();

            if (!$secretKey || !$pendingUser) return false;

            $authenticated = $this->twoFactorProvider->authenticate($secretKey, $code);

            if ($authenticated) {
                $this->session->__setPendingLoggedInUser(null);

                $this->securityService->logIn($pendingUser);
                return true;
            }
        } else if (strlen($code) === 9) {
            $backupCodes = $pendingUser->getBackupCodes();

            if (($key = array_search($code, $backupCodes)) !== false) {
                unset($backupCodes[$key]);
                $user = User::fetch($pendingUser->getId());
                $user->setBackupCodes(array_values($backupCodes));
                $user->save();

                $this->session->__setPendingLoggedInUser(null);

                $this->securityService->logIn($pendingUser);
                return true;
            }
        }

        return false;
    }


    /**
     * Authenticate by a user token and optionally a secondary access token if this
     * has been added.
     *
     * @param string $userAccessToken
     * @param string $secondaryAccessToken
     *
     * @objectInterceptorDisabled
     */
    public function authenticateByUserToken($userAccessToken, $secondaryAccessToken = null) {

        $hashProvider = new SHA512HashProvider();
        $hashValue = $hashProvider->generateHash($userAccessToken . ($secondaryAccessToken ? "--" . $secondaryAccessToken : ""));

        if ($hashValue != $this->session->__getLoggedInUserAccessTokenHash()) {

            $matches = UserAccessToken::filter("WHERE token_hash = ?", $hashValue);

            if (sizeof($matches) > 0) {
                $user = User::fetch($matches[0]->getUserId());
                $this->securityService->login($user, null, $hashValue);
            } else {
                throw new InvalidUserAccessTokenException();
            }
        }

    }


    /**
     * Authenticate an account by key and secret
     *
     * @param $apiKey
     * @param $apiSecret
     *
     * @objectInterceptorDisabled
     */
    public function apiAuthenticate($apiKey, $apiSecret) {

        $matchingAccounts = Account::filter("WHERE apiKey = ? AND apiSecret = ?", $apiKey, $apiSecret);

        // If there is a matching user, return it now.
        if (sizeof($matchingAccounts) > 0) {
            $this->securityService->login(null, $matchingAccounts[0]);
        } else {
            throw new InvalidAPICredentialsException();
        }


    }


    /**
     * Update the active parent URL according to a referring URL.
     *
     * @param URL $referringURL
     */
    public function updateActiveParentAccount($referringURL) {

        if (!$referringURL) {
            $this->session->__setValidReferrer(false);
        } else {

            $referrer = $referringURL->getHost();


            // If the referer differs from the session value, check some stuff.
            if ($referrer !== $this->session->__getReferringURL()) {

                $this->session->__setReferringURL($referrer);


                // Now attempt to look up the setting by key and value
                $setting = $this->settingsService->getSettingByKeyAndValue("referringDomains", $referrer);
                if ($setting) {
                    $parentAccountId = $setting->getParentAccountId();
                    $this->session->__setValidReferrer(true);
                } else {
                    $parentAccountId = null;
                    $this->session->__setValidReferrer(false);
                }


                // Make sure we log out if the active parent account id has changed.
                if ($this->session->__getActiveParentAccountId() != $parentAccountId) {
                    $this->logOut();
                }

                $this->session->__setActiveParentAccountId($parentAccountId);

            }
        }

        if (!$this->session->__getValidReferrer()) {
            throw new InvalidReferrerException();
        }

    }

    /**
     * Get the active referrer
     */
    public function hasActiveReferrer() {
        return $this->session->__getValidReferrer();
    }


    /**
     * Log out function.
     */
    public function logout() {
        $this->securityService->logOut();
    }


}
