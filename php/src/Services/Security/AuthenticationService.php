<?php


namespace Kiniauth\Services\Security;


use Kiniauth\Exception\Security\AccountSuspendedException;
use Kiniauth\Exception\Security\InvalidAPICredentialsException;
use Kiniauth\Exception\Security\InvalidLoginException;
use Kiniauth\Exception\Security\InvalidReferrerException;
use Kiniauth\Exception\Security\InvalidUserAccessTokenException;
use Kiniauth\Exception\Security\UserSuspendedException;
use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserAccessToken;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Application\ActivityLogger;
use Kiniauth\Services\Security\SSOProvider\AppleSSOAuthenticator;
use Kiniauth\Services\Security\SSOProvider\FacebookSSOAuthenticator;
use Kiniauth\Services\Security\SSOProvider\GoogleSSOAuthenticator;
use Kiniauth\Services\Security\TwoFactor\TwoFactorProvider;
use Kiniauth\Services\Workflow\PendingActionService;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Security\Hash\HashProvider;
use Kinikit\Core\Security\Hash\SHA512HashProvider;
use Kinikit\MVC\Request\Request;
use Kinikit\MVC\Request\URL;


/**
 * AuthenticationService object for coordinating authentication functions for Kiniauth.
 *
 * Class AuthenticationService
 * @package Kiniauth\Workers\Application
 *
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

    /**
     * @var PendingActionService
     */
    private $pendingActionService;

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
     * @param PendingActionService $pendingActionService
     */
    public function __construct($settingsService, $session, $securityService, $twoFactorProvider, $hashProvider, $userService, $userSessionService,
                                $pendingActionService) {
        $this->settingsService = $settingsService;
        $this->session = $session;
        $this->securityService = $securityService;
        $this->twoFactorProvider = $twoFactorProvider;
        $this->hashProvider = $hashProvider;
        $this->userService = $userService;
        $this->userSessionService = $userSessionService;
        $this->pendingActionService = $pendingActionService;
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
    public function login($emailAddress, $password, $clientTwoFactorData = null, $parentAccountId = null) {

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
                        $this->session->__setPendingTwoFactorData($clientTwoFactorData);
                        return self::STATUS_ACTIVE_SESSION;
                    }
                }

                $sessionTwoFactorData = $this->twoFactorProvider->generateTwoFactorIfRequired($user, $clientTwoFactorData);

                if ($sessionTwoFactorData !== false) {
                    $this->session->__setPendingLoggedInUser($user);
                    $this->session->__setPendingTwoFactorData($sessionTwoFactorData);
                    return self::STATUS_REQUIRES_2FA;
                } else {
                    $this->securityService->logIn($user);
                    ActivityLogger::log("Logged in");
                    return self::STATUS_LOGGED_IN;
                }
            } else {

                // Invalid password
                if ($user->getStatus() == User::STATUS_ACTIVE && $maxLoginAttempts = Configuration::readParameter("login.max.attempts")) {


                    $existingLoginAttempts = $user->getInvalidLoginAttempts();
                    $user->setInvalidLoginAttempts($existingLoginAttempts + 1);

                    ActivityLogger::log("Failed Login (Invalid Password)", null, null, [], $user->getId());

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
            /**
             * @var Request $request
             */
            $request = Container::instance()->get(Request::class);
            ActivityLogger::log("Unknown Login Attempt", null, null, ["ipAddress" =>
                $request->getRemoteIPAddress()]);
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


            // Work out if we need to generate a two factor
            $sessionTwoFactorData = $this->twoFactorProvider->generateTwoFactorIfRequired($pendingUser,
                $this->session->__getPendingTwoFactorData());

            if ($sessionTwoFactorData !== false) {
                $this->session->__setPendingLoggedInUser($pendingUser);
                $this->session->__setPendingTwoFactorData($sessionTwoFactorData);
                return self::STATUS_REQUIRES_2FA;
            } else {
                $this->session->__setPendingLoggedInUser(null);
                $this->securityService->logIn($pendingUser);
                ActivityLogger::log("Logged in");
                return self::STATUS_LOGGED_IN;
            }

        } else {
            throw new InvalidLoginException("No pending login");
        }
    }

    /**
     * Check the supplied two factor code and authenticate the login if correct.
     *
     * @param $twoFactorLoginData
     * @return mixed
     *
     * @throws InvalidLoginException
     * @throws AccountSuspendedException
     * @throws UserSuspendedException
     *
     * @objectInterceptorDisabled
     */
    public function authenticateTwoFactor($twoFactorLoginData) {

        $pendingUser = $this->session->__getPendingLoggedInUser();

        if (!$pendingUser) {
            throw new InvalidLoginException("Two factor authentication called out of sequence");
        }

        if (Configuration::readParameter("login.single.session")) {
            $otherSessions = $this->userSessionService->listAuthenticatedSessions($pendingUser->getId());
            if (sizeof($otherSessions) > 0) {
                throw new InvalidLoginException("Active session exists");
            }
        }

        $pendingTwoFactorData = $this->session->__getPendingTwoFactorData();

        // Authenticate
        $result = $this->twoFactorProvider->authenticate($pendingUser, $pendingTwoFactorData, $twoFactorLoginData);

        if ($result !== false) {
            $this->session->__setPendingLoggedInUser(null);
            $this->session->__setPendingTwoFactorData(null);

            $this->securityService->logIn($pendingUser);
            ActivityLogger::log("Logged in");
            return $result;
        }

        ActivityLogger::log("Failed Login (Invalid 2FA)", null, null, [], $pendingUser->getId());
        throw new InvalidLoginException("Invalid Two Factor Authentication Supplied");
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
                ActivityLogger::log("User Token Login");
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

        $matchingKeys = APIKey::filter("WHERE apiKey = ? AND apiSecret = ?", $apiKey, $apiSecret);

        // If there is a matching api key, return it now.
        if (sizeof($matchingKeys) > 0) {
            $this->securityService->login($matchingKeys[0], null);
            ActivityLogger::log("API Login");
        } else {
            throw new InvalidAPICredentialsException();
        }


    }


    /**
     * Generate a random session transfer token
     */
    public function generateSessionTransferToken() {

        $loggedInUser = $this->session->__getLoggedInSecurable();

        // If not a user logged in thrown
        if (!$loggedInUser || !($loggedInUser instanceof User)) {
            throw new AccessDeniedException("Not logged in");
        }

        $this->pendingActionService->removeAllPendingActionsForTypeAndObjectId("Session Token", $loggedInUser->getId(), "User");

        // Create a pending action
        $sessionToken = $this->pendingActionService->createPendingAction("Session Token", $loggedInUser->getId(), $this->session->getId(),
            "PT1M", null, "User");


        // Return the session token
        return $sessionToken;

    }


    /**
     * Activate a session using transfer token
     *
     */
    public function activateSessionUsingTransferToken($sessionToken) {

        try {
            $action = $this->pendingActionService->getPendingActionByIdentifier("Session Token", $sessionToken);
        } catch (ItemNotFoundException $e) {
            throw new AccessDeniedException("Invalid session");
        }

        // Join a session identified by the passed data
        $this->session->join($action->getData());

        $this->pendingActionService->removePendingAction("Session Token", $sessionToken);

        return true;

    }


    /**
     * Create a join account one time token for administrator to join a GSE account
     *
     * @param $accountId
     * @return string
     */
    public function createJoinAccountToken($accountId){

        $loggedInUser = $this->session->__getLoggedInSecurable();

        // If not a user logged in thrown
        if (!$loggedInUser || !($loggedInUser instanceof User)) {
            throw new AccessDeniedException("Not logged in");
        }

        $this->pendingActionService->removeAllPendingActionsForTypeAndObjectId("JOIN_ACCOUNT_TOKEN", $loggedInUser->getId(), User::class);

        // Create a pending action
        $joinAccountToken = $this->pendingActionService->createPendingAction("JOIN_ACCOUNT_TOKEN", $loggedInUser->getId(), $accountId,
            "PT1M", null, User::class);


        // Return the join account token
        return $joinAccountToken;

    }



    public function joinAccountUsingToken($token){

        try {
            $action = $this->pendingActionService->getPendingActionByIdentifier("JOIN_ACCOUNT_TOKEN", $token);
        } catch (ItemNotFoundException $e) {
            throw new AccessDeniedException("Invalid token");
        }


        // Join an account using the token action
        $this->securityService->becomeSecurable("USER", $action->getObjectId(), $action->getData());

        $this->pendingActionService->removePendingAction("JOIN_ACCOUNT_TOKEN", $token);

    }



    /**
     * Update the active parent URL according to a referring URL.
     *
     * @param URL $referringURL
     */
    public function updateActiveParentAccount($referringURL) {


        if (!$referringURL) {
            $this->session->__setValidReferrer(false);
            $this->session->__setReferringURL(null);
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
                    $this->session->__setReferringURL(null);
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


    /**
     * Perform single sign-on with the relevant provider
     *
     * @param $provider
     * @param $data
     * @return void
     * 
     * @objectInterceptorDisabled
     */
    public function authenticateBySSO($provider, $data) {

        switch ($provider) {
            case "facebook":
                $authenticator = Container::instance()->get(FacebookSSOAuthenticator::class);
                $email = $authenticator->authenticate($data);
                break;
            case "google":
                $authenticator = Container::instance()->get(GoogleSSOAuthenticator::class);
                $email = $authenticator->authenticate($data);
                break;
            case "apple":
                $authenticator = Container::instance()->get(AppleSSOAuthenticator::class);
                $email = $authenticator->authenticate($data);
                break;
            default:
                $email = null;
                break;
        }

        $user = $this->userService->getUserByEmail($email);

        if ($user) {
            $this->securityService->login($user);
        } else {
            throw new \Exception("User doesn't have an account");
        }

    }

}
