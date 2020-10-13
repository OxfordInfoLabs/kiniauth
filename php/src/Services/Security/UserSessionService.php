<?php


namespace Kiniauth\Services\Security;

use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Objects\Security\UserSession;
use Kiniauth\Objects\Security\UserSessionProfile;
use Kiniauth\Services\Communication\Email\EmailService;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\MVC\Request\Request;
use Kinikit\MVC\Session\Session;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

/**
 * Manage persisted user sessions
 *
 * Class UserSessionService
 * @package Kiniauth\Services\Security
 */
class UserSessionService {

    /**
     * @var Session
     */
    private $session;

    /**
     * @var EmailService
     */
    private $emailService;


    /**
     * UserSessionService constructor.
     *
     * @param Session $session
     * @param EmailService $emailService
     */
    public function __construct($session, $emailService) {
        $this->session = $session;
        $this->emailService = $emailService;
    }


    /**
     * Register a new authenticated session for the passed user and request
     *
     * @param integer $userId
     * @param Request $request
     */
    public function registerNewAuthenticatedSession($userId, $request = null) {

        if (!$request) {
            $request = Container::instance()->get(Request::class);
        }

        // Grab the key fields
        $ipAddress = $request->getRemoteIPAddress();

        if ($ipAddress)
            $ipAddress = trim(explode(",", $ipAddress)[0]);


        $userAgent = $request->getHeaders()->getUserAgent();

        // Create the profile
        $userSessionProfile = new UserSessionProfile($ipAddress, $userAgent, $userId);

        // Check whether it already exists or not
        try {
            UserSessionProfile::fetch([$userId, $userSessionProfile->getProfileHash()]);
        } catch (ObjectNotFoundException $e) {

            // If not exists, ensure this is not the first profile for this user
            $existingProfiles = UserSessionProfile::values("COUNT(*)", "WHERE userId = ?", $userId);
            if ($existingProfiles[0] > 0) {
                $userEmail = new UserTemplatedEmail($userId, "security/new-device", [
                    "ipAddress" => $ipAddress,
                    "userAgent" => $userAgent
                ]);

                $this->emailService->send($userEmail, null, $userId);
            }

        }

        $userSession = new UserSession($userId, $this->session->getId(), $userSessionProfile);

        // Save the user session.
        $userSession->save();

    }


    /**
     * List active authenticated sessions
     *
     * @param $userId
     */
    public function listAuthenticatedSessions($userId) {

        $userSessions = UserSession::filter("WHERE userId = ? ORDER BY createdDateTime DESC", $userId);

        $activeSessions = [];
        foreach ($userSessions as $userSession) {
            if ($this->session->isActive($userSession->getSessionId())) {
                $activeSessions[] = $userSession;
            } else {
                $userSession->remove();
            }
        }


        return $activeSessions;

    }


    /**
     * Terminate an authenticated session for a user and session id.
     *
     * @param integer $userId
     * @param string $sessionId
     */
    public function terminateAuthenticatedSession($userId, $sessionId) {

        // Destroy the session
        $this->session->destroy($sessionId);

        try {
            $existingSession = UserSession::fetch([$userId, $sessionId]);
            $existingSession->remove();
        } catch (ObjectNotFoundException $e) {
            // Gone anyway
        }


    }

}
