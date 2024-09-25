<?php


namespace Kiniauth\Traits\Controller\Guest;


use Kiniauth\Services\Application\SessionData;
use Kinikit\Core\DependencyInjection\Container;


trait Session {

    /**
     * @var \Kiniauth\Services\Application\Session
     */
    private $session;


    /**
     * @param \Kiniauth\Services\Application\Session $session
     */
    public function __construct($session) {
        $this->session = $session;
    }

    /**
     * Return the logged in user/account
     *
     * @http GET /
     *
     * @return mixed
     */
    public function getSessionData() {
        $this->session->__clearCSRFToken();
        return Container::instance()->get(SessionData::class);
    }

}
