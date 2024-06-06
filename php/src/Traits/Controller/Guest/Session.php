<?php


namespace Kiniauth\Traits\Controller\Guest;


use Kiniauth\Services\Application\SessionData;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;


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
