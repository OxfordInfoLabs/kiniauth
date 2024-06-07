<?php

namespace Kiniauth\Traits\Controller\Guest;

use Kiniauth\Services\Security\ObjectScopeAccessService;
use Kiniauth\ValueObjects\Security\SharableItem;

trait Sharing {

    /**
     * @param ObjectScopeAccessService $objectScopeAccessService
     */
    public function __construct(private ObjectScopeAccessService $objectScopeAccessService) {
    }


    /**
     * Get a sharable item for a passed invite
     *
     * @http GET /$invitationCode
     *
     * @param $invitationCode
     * @return SharableItem
     */
    public function getSharableItemForInvite($invitationCode) {
        return $this->objectScopeAccessService->getSharableItemForInvitationCode($invitationCode);
    }


    /**
     * @http POST /$invitationCode
     *
     * @param $invitationCode
     *
     * @return boolean
     */
    public function acceptSharingInvite($invitationCode) {
        $this->objectScopeAccessService->acceptAccountInvitationToShareObject($invitationCode);
        return true;
    }

    /**
     * @http DELETE /$invitationCode
     *
     * @param $invitationCode
     *
     * @return boolean
     */
    public function rejectSharingInvite($invitationCode) {
        $this->objectScopeAccessService->rejectAccountInvitationToShareObject($invitationCode);
        return true;
    }


}