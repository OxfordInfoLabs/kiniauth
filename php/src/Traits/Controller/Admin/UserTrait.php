<?php

namespace Kiniauth\Traits\Controller\Admin;

use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserSummary;
use Kiniauth\ValueObjects\Registration\NewUserDescriptor;
use Kiniauth\ValueObjects\Security\UserExtended;

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
     * @return UserExtended
     */
    public function getUser($userId = User::LOGGED_IN_USER) {
        $user = User::fetch($userId);
        return new UserExtended($user);
    }


    /**
     * Create a new user using a new user descriptor.
     *
     * @http POST /admin
     *
     * @param NewUserDescriptor $newUserDescriptor
     */
    public function createAdminUser($newUserDescriptor) {
        return $this->userService->createAdminUser($newUserDescriptor->getEmailAddress(), $newUserDescriptor->getPassword(), $newUserDescriptor->getName());
    }


    /**
     * Get a user object by userId (optional), defaults to the logged in user
     *
     * @http GET /summary
     *
     * @param string $userId
     *
     * @return UserSummary
     */
    public function getUserSummary($userId = User::LOGGED_IN_USER) {
        return UserSummary::fetch($userId);
    }


    /**
     * Search for account users
     *
     * @http GET /search
     *
     * @param string $searchString
     * @param int $offset
     * @param int $limit
     * @param int $accountId
     * @return array
     */
    public function searchForUsers($searchString = "", $offset = 0, $limit = 10, $accountId = null) {
        return $this->userService->searchForUsers($searchString, $offset, $limit, $accountId);
    }

    /**
     * Search for Admin users
     *
     * @http GET /adminSearch
     *
     * @param string $searchString
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function searchForAdminUsers($searchString = "", $offset = 0, $limit = 10) {
        return $this->userService->searchForUsers($searchString, $offset, $limit, 0);
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



    /**
     * Request a password reset
     *
     * @http GET /passwordReset
     *
     * @param $emailAddress
     *
     */
    public function requestPasswordReset($emailAddress) {
        $this->userService->sendPasswordReset($emailAddress);
    }

    /**
     * Unlock a user account
     *
     * @http GET /unlock
     *
     * @param $userId
     */
    public function unlockUser($userId) {
        $this->userService->unlockUserByUserId($userId);
    }

    /**
     * Suspend a user
     *
     * @http GET /suspend
     *
     * @param $userId
     */
    public function suspendUser($userId) {
        $this->userService->suspendUser($userId);
    }
}
