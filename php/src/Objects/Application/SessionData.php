<?php


namespace Kiniauth\Objects\Application;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Objects\Security\Securable;
use Kiniauth\Objects\Security\User;
use Kinikit\Core\Object\SerialisableObject;

/**
 * Simple container containing common session data for return back to the
 * application for display purposes etc.
 *
 * @package Kiniauth\Objects\Application
 */
class SessionData {

    /**
     * @var User
     */
    private $user;

    /**
     * @var APIKey
     */
    private $apiKey;

    /**
     * @var AccountSummary
     */
    private $account;


    /**
     * Get session data using user and account objects to seed the data.
     *
     * SessionData constructor.
     * @param Securable $securable
     * @param Account $account
     */
    public function __construct($securable = null, $account = null) {
        $this->user = $securable instanceof User ? $securable : null;
        $this->apiKey = $securable instanceof APIKey ? $securable : null;
        $this->account = $account ? $account->generateSummary() : null;
    }

    /**
     * @return User
     */
    public function getUser() {
        return $this->user;
    }


    /**
     * @return APIKey
     */
    public function getApiKey() {
        return $this->apiKey;
    }

    /**
     * @return AccountSummary
     */
    public function getAccount() {
        return $this->account;
    }


}
