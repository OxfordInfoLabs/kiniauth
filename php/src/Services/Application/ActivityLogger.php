<?php


namespace Kiniauth\Services\Application;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Application\Activity;
use Kiniauth\Objects\Security\User;
use Kinikit\Core\DependencyInjection\Container;

class ActivityLogger {


    /**
     * @var Session
     */
    private $session;


    /**
     * ActivityLogger constructor.
     *
     * @param Session $session
     */
    public function __construct($session) {
        $this->session = $session;
    }


    /**
     * Log an event - implemented as a static function for application wide convenience
     *
     * @param $event
     * @param null $associatedObjectId
     * @param null $associatedObjectDescription
     * @param array $data
     * @param null $userId
     * @param null $accountId
     */
    public static function log($event, $associatedObjectId = null, $associatedObjectDescription = null, $data = [], $userId = User::LOGGED_IN_USER, $accountId = Account::LOGGED_IN_ACCOUNT, $transactionId = null) {

        /**
         * @var ActivityLogger $logger
         */
        $logger = Container::instance()->get(ActivityLogger::class);

        /**
         * Create a log
         */
        $logger->createLog($event, $associatedObjectId, $associatedObjectDescription, $data, $transactionId, $userId, $accountId);

    }


    /**
     * Create a log entry
     *
     * @param $event
     * @param string $associatedObjectId
     * @param string $associatedObjectDescription
     * @param  $data
     * @param null $accountId
     * @param null $userId
     *
     * @objectInterceptorDisabled
     */
    public function createLog($event, $associatedObjectId = null, $associatedObjectDescription = null, $data = [], $transactionId = null, $userId = User::LOGGED_IN_USER, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Logged in user id.
        $loggedInSecurable = $this->session->__getLoggedInSecurable() ? ($this->session->__getLoggedInSecurable() instanceof User ? "USER" : "API_KEY") : null;
        $loggedInUserId = $this->session->__getLoggedInSecurable() ? $this->session->__getLoggedInSecurable()->getId() : null;

        // Save activity log
        $logEntry = new Activity($userId, $accountId, $event, $associatedObjectId, $associatedObjectDescription, $data, $loggedInSecurable, $loggedInUserId,$transactionId);
        $logEntry->save();


    }

}
