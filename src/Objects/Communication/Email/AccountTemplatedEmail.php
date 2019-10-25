<?php


namespace Kiniauth\Objects\Communication\Email;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Security\AccountScopeAccess;
use Kiniauth\Services\Security\ScopeAccess;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\Core\Communication\Email\Attachment\EmailAttachment;
use Kinikit\Core\DependencyInjection\Container;

/**
 * Class AccountTemplatedEmail
 * @package Kiniauth\Objects\Communication\Email
 */
class AccountTemplatedEmail extends BrandedTemplatedEmail {

    /**
     * AccountTemplatedEmail constructor.
     *
     * @param integer $accountId
     * @param string $templateName
     * @param mixed[] $model
     * @param EmailAttachment[] $attachments
     * @throws \Kinikit\Core\Communication\Email\MissingEmailTemplateException
     * @throws \Kinikit\Core\Validation\ValidationException
     */
    public function __construct($accountId, $templateName, $model = [], $attachments = []) {


        /**
         * @var SecurityService $securityService
         */
        $securityService = Container::instance()->get(SecurityService::class);

        $loggedInUserAndAccount = $securityService->getLoggedInUserAndAccount();
        $account = null;
        if (isset($loggedInUserAndAccount[1]) && $loggedInUserAndAccount[1]->getAccountId() == $accountId) {
            $account = $loggedInUserAndAccount[1];
        } else {
            $account = Account::fetch($accountId);
        }

        $model["account"] = $account;

        // Parse the template
        $data = $this->parseTemplate($templateName, $model);


        $recipients = null;
        if (!isset($data["to"])) {

            $userService = Container::instance()->get(UserService::class);
            $users = $userService->getUsersWithRole(Role::SCOPE_ACCOUNT, $accountId);
            $recipients = [];
            foreach ($users as $user) {
                $recipients[] = $user->getFullEmailAddress();
            }

        }


        parent::__construct($templateName, $model, $accountId, null, $recipients, $attachments);
    }

}
