<?php


namespace Kiniauth\Objects\Communication\Email;


use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kinikit\Core\DependencyInjection\Container;

class SuperUserTemplatedEmail extends BrandedTemplatedEmail {

    public function __construct($templateName, $model = [], $attachments = []) {

        // Parse the template
        $data = $this->parseTemplate($templateName, $model);

        $recipients = [];
        if (!isset($data["to"])) {

            Container::instance()->get(ActiveRecordInterceptor::class)->executeInsecure(function () use (&$recipients) {

                // Grab all superusers and merge them in.
                $userService = Container::instance()->get(UserService::class);
                $users = $userService->getUsersWithRole(Role::SCOPE_ACCOUNT, 0, 0);
                foreach ($users as $user) {
                    $recipients[] = $user->getFullEmailAddress();
                }

            });

        }

        parent::__construct($templateName, $model, null, null, $recipients, $attachments);
    }

}
