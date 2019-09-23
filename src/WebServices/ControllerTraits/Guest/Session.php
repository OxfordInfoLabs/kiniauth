<?php


namespace Kiniauth\WebServices\ControllerTraits\Guest;


use Kiniauth\Objects\Application\SessionData;


trait Session {

    private $sessionService;

    /**
     * @param \Kiniauth\Services\Application\SessionService $sessionService
     */
    public function __construct($sessionService) {
        $this->sessionService = $sessionService;
    }

    /**
     * Return the logged in user/account
     *
     * @http GET /
     *
     * @return SessionData
     */
    public function getSessionData() {
        return $this->sessionService->getSessionData();
    }

}
