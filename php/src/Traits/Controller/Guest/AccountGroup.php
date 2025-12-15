<?php

namespace Kiniauth\Traits\Controller\Guest;

use Kiniauth\Objects\Account\AccountGroupMember;
use Kiniauth\Services\Account\AccountGroupService;
use Kiniauth\ValueObjects\Account\AccountGroupDescriptor;
use Kiniauth\ValueObjects\Account\AccountGroupInvitation;

trait AccountGroup {

    private $accountGroupService;

    /**
     * Account constructor.
     * @param AccountGroupService $accountGroupService
     */
    public function __construct($accountGroupService) {
        $this->accountGroupService = $accountGroupService;
    }

    /**
     * Get the details for an invitation using an invitation code.
     *
     * @http GET /invitation/$invitationCode
     *
     * @param string $invitationCode
     *
     * @return AccountGroupInvitation
     */
    public function getInvitationDetails($invitationCode) {
        return $this->accountGroupService->getInvitationDetails($invitationCode);
    }

    /**
     * Accept an invitation to join an account group
     *
     * @http POST /invitation/$invitationCode
     *
     * @param string $invitationCode
     *
     * @return void
     */
    public function acceptInviteToAccountGroup($invitationCode) {
        $this->accountGroupService->acceptAccountGroupInvitation($invitationCode);
    }

}