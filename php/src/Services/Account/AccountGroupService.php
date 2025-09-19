<?php

namespace Kiniauth\Services\Account;

use Kiniauth\Objects\Account\AccountGroup;
use Kiniauth\Objects\Account\AccountGroupMember;

class AccountGroupService {

    /**
     * @return AccountGroup[]
     */
    public function getAccountGroups() {
        return AccountGroup::filter();
    }

    /**
     * @param string $name
     * @param int $ownerAccountId
     * @return void
     */
    public function createAccountGroup(string $name, int $ownerAccountId): void {
        $accountGroup = new AccountGroup($name, $ownerAccountId);
        $accountGroup->save();
    }

    /**
     * @param int $accountGroupId
     * @return array
     */
    public function getMembersOfAccountGroup(int $accountGroupId): array {
        return AccountGroupMember::filter("WHERE account_group_id = ?", $accountGroupId);
    }

    /**
     * @param int $accountGroupId
     * @param int $accountId
     * @return void
     */
    public function addMemberToAccountGroup(int $accountGroupId, int $accountId): void {
        $accountGroupMember = new AccountGroupMember($accountGroupId, $accountId);
        $accountGroupMember->save();
    }

    /**
     * @param int $accountGroupId
     * @param int $accountId
     * @return void
     */
    public function removeMemberFromAccountGroup(int $accountGroupId, int $accountId): void {
        try {
            /** @var AccountGroupMember $accountGroupMember */
            $accountGroupMember = AccountGroupMember::fetch([$accountGroupId, $accountId]);
            $accountGroupMember->remove();
        } catch (ObjectNotFoundException $e) {
            return;
        }

    }

}