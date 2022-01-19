<?php


namespace Kiniauth\Services\Application;


use Kiniauth\Objects\Application\SessionData;

class SessionService {

    private $session;

    /**
     * Construct with authentication service
     *
     * @param \Kiniauth\Services\Application\Session $session
     */
    public function __construct($session) {
        $this->session = $session;
    }


    /**
     * Get session data for current logged in state.
     */
    public function getSessionData() {
        return new SessionData($this->session->__getLoggedInSecurable(), $this->session->__getLoggedInAccount());
    }

}
