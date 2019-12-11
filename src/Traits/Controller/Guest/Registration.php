<?php


namespace Kiniauth\Traits\Controller\Guest;


use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Account\UserService;
use Kiniauth\ValueObjects\Registration\NewUserAccountDescriptor;
use Kinikit\Core\Logging\Logger;

/**
 * Trait Register
 * @package Kiniauth\WebServices\ControllerTraits\Guest
 */
trait Registration {

    private $userService;

    private $accountService;

    /**
     * Construct with new user service
     *
     * Registration constructor.
     *
     * @param UserService $userService
     * @param AccountService $accountService
     */
    public function __construct($userService, $accountService) {
        $this->userService = $userService;
        $this->accountService = $accountService;
    }


    /**
     * Create a new registration using the passed descriptor.
     *
     * @http POST /create
     *
     * @param NewUserAccountDescriptor $userAccountDescriptor
     */
    public function createUserWithAccount($userAccountDescriptor) {
        $this->userService->createWithAccount($userAccountDescriptor->getEmailAddress(), $userAccountDescriptor->getPassword(), $userAccountDescriptor->getName(), $userAccountDescriptor->getAccountName());
    }


    /**
     * Activate a user account using a code
     *
     * @http GET /activate/$activationCode
     *
     * @param $activationCode
     */
    public function activateUserAccount($activationCode) {
        $this->userService->activateAccount($activationCode);
    }


    /**
     * Get the details for an invitation using an invitation code.
     *
     * @http GET /invitation/$invitationCode
     *
     * @param $invitationCode
     */
    public function getInvitationDetails($invitationCode) {
        return $this->accountService->getInvitationDetails($invitationCode);
    }


    /**
     * Accept an invitation using the supplied user account descriptor if required.
     *
     * @http POST /invitation/$invitationCode
     *
     * @param string $invitationCode
     * @param NewUserAccountDescriptor $userAccountDescriptor
     */
    public function acceptInvitation($invitationCode, $userAccountDescriptor) {
        $this->accountService->acceptUserInvitationForAccount($invitationCode, $userAccountDescriptor->getPassword(), $userAccountDescriptor->getName());
    }


}
