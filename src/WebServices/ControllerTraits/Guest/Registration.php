<?php


namespace Kiniauth\WebServices\ControllerTraits\Guest;


use Kiniauth\Services\Account\UserService;
use Kiniauth\WebServices\ValueObjects\Registration\NewUserAccountDescriptor;
use Kinikit\Core\Logging\Logger;

/**
 * Trait Register
 * @package Kiniauth\WebServices\ControllerTraits\Guest
 */
trait Registration {

    private $userService;

    /**
     * Construct with new user service
     *
     * Registration constructor.
     *
     * @param UserService $userService
     */
    public function __construct($userService) {
        $this->userService = $userService;
    }


    /**
     * Create a new registration using the passed descriptor.
     *
     * @http POST /create
     *
     * @param NewUserAccountDescriptor $userAccountDescriptor
     */
    public function createUserWithAccount($userAccountDescriptor) {

        $this->userService->createWithAccount($userAccountDescriptor->getEmailAddress(), $userAccountDescriptor->getPassword(), null, $userAccountDescriptor->getAccountName());

    }


    /**
     * Activate a user account using a code
     *
     * @http GET /activate/$activationCode
     *
     * @param $activationCode
     */
    public function activateUserAccount($activationCode){
        $this->userService->activateAccount($activationCode);
    }

}
