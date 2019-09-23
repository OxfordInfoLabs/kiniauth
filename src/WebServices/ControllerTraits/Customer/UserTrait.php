<?php

namespace Kiniauth\WebServices\ControllerTraits\Customer;

use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Account\UserService;

trait UserTrait {

    private $userService;

    /**
     * Account constructor.
     * @param \Kiniauth\Services\Account\UserService $userService
     */
    public function __construct($userService) {
        $this->userService = $userService;
    }

    /**
     * Get a user object by userId (optional), defaults to the logged in user
     *
     * @http GET
     *
     * @param string $userId
     *
     * @return User
     */
    public function getUser($userId = User::LOGGED_IN_USER) {
        return User::fetch($userId);
    }

    /**
     * Change the logged in users email
     *
     * @http GET /changeEmail
     *
     * @param $newEmailAddress
     * @param $password
     * @return \Kiniauth\Objects\Security\User
     */
    public function changeUserEmail($newEmailAddress, $password) {
        return $this->userService->changeUserEmail($newEmailAddress, $password);
    }

    /**
     * Change the logged in user backup email
     *
     * @http GET /changeBackupEmail
     *
     * @param $newEmailAddress
     * @param $password
     * @return \Kiniauth\Objects\Security\User
     */
    public function changeUserBackupEmail($newEmailAddress, $password) {
        return $this->userService->changeUserBackupEmail($newEmailAddress, $password);
    }

    /**
     * Change the logged in user mobile number
     *
     * @http GET /changeMobile
     *
     * @param $newMobile
     * @param $password
     * @return \Kiniauth\Objects\Security\User
     */
    public function changeUserMobile($newMobile, $password) {
        return $this->userService->changeUserMobile($newMobile, $password);
    }
}
