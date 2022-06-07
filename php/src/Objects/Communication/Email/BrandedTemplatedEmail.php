<?php


namespace Kiniauth\Objects\Communication\Email;

use Kiniauth\Services\Application\SettingsService;
use Kinikit\Core\Communication\Email\TemplatedEmail;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Template\TemplateParser;

/**
 * Base branded templated email which adds parent account settings to the model.
 * Used by the Account and User subclasses
 *
 * Class BrandedTemplatedEmail
 * @package Kiniauth\Objects\Communication\Email
 */
class BrandedTemplatedEmail extends TemplatedEmail {

    public function __construct($templateName, $model = [], $accountId = null, $userId = null, $recipients = null, $attachments = []) {

        /**
         * @var SettingsService $settingsService
         */
        $settingsService = Container::instance()->get(SettingsService::class);
        $settings = $model["settings"] = $settingsService->getParentAccountSettingValues($accountId, $userId);

        $templateData = $this->parseTemplate($templateName, $model);
        $from = null;
        $replyTo = null;
        if (!isset($templateData["from"])) {
            $from = $settings["fromEmailAddress"] ?? null;
        }
        if (!isset($templateData["replyto"])) {
            $replyTo = $settings["replyToEmailAddress"] ?? null;
        }

        parent::__construct($templateName, $model, $recipients, $from, null, null, null, $replyTo, $attachments);
    }

}
