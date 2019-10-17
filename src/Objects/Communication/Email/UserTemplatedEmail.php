<?php


namespace Kiniauth\Objects\Communication\Email;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\Core\Communication\Email\Attachment\EmailAttachment;
use Kinikit\Core\DependencyInjection\Container;

/**
 * Class UserTemplatedEmail
 * @package Kiniauth\Objects\Communication\Email
 * @noGenerate
 */
class UserTemplatedEmail extends BrandedTemplatedEmail {

    /**
     * AccountTemplatedEmail constructor.
     *
     * @param integer $userId
     * @param string $templateName
     * @param mixed[] $model
     * @param EmailAttachment[] $attachments
     * @throws \Kinikit\Core\Communication\Email\MissingEmailTemplateException
     * @throws \Kinikit\Core\Validation\ValidationException
     */
    public function __construct($userId, $templateName, $model = [], $attachments = []) {


        /**
         * @var SecurityService $securityService
         */
        $securityService = Container::instance()->get(SecurityService::class);

        $loggedInUserAndAccount = $securityService->getLoggedInUserAndAccount();
        $user = null;
        if (isset($loggedInUserAndAccount[0]) && $loggedInUserAndAccount[0]->getId() == $userId) {
            $user = $loggedInUserAndAccount[0];
        } else {
            $user = User::fetch($userId);
        }

        $model["user"] = $user;

        // Parse the template
        $data = $this->parseTemplate($templateName, $model);


        $recipients = null;
        if (!isset($data["to"])) {
            $recipients = [$user->getFullEmailAddress()];
        }


        parent::__construct($templateName, $model, null, $userId, $recipients, $attachments);


    }
}
