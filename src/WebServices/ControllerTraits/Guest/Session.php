<?php


namespace Kiniauth\WebServices\ControllerTraits\Guest;


use Kiniauth\Services\Application\SessionData;
use Kinikit\Core\DependencyInjection\Container;


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
     * @return mixed
     */
    public function getSessionData() {
        return Container::instance()->get(SessionData::class);
    }

}
