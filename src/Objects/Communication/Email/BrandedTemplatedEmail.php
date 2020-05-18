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
abstract class BrandedTemplatedEmail extends TemplatedEmail {

    public function __construct($templateName, $model = [], $accountId = null, $userId = null, $recipients = null, $attachments = []) {

        /**
         * @var SettingsService $settingsService
         */
        $settingsService = Container::instance()->get(SettingsService::class);
        $settings = $model["settings"] = $settingsService->getParentAccountSettingValues($accountId, $userId);

        $templateData = $this->parseTemplate($templateName, $model);
        $from = null;
        if (!isset($templateData["from"])) {
            $from = $settings["fromEmailAddress"] ?? null;
        }
        if (!isset($templateData["replyto"])) {
            $replyTo = $settings["replyToEmailAddress"] ?? null;
        }

        /**
         * Create a file resolver
         *
         * @var FileResolver $fileResolver
         */
        $fileResolver = Container::instance()->get(FileResolver::class);

        /**
         * Create a template parser
         *
         * @var TemplateParser $templateParser
         */
        $templateParser = Container::instance()->get(TemplateParser::class);

        // Header
        $header = $fileResolver->resolveFile("Config/email-templates/header.html");
        if ($header) {
            $model["header"] = $templateParser->parseTemplateText(file_get_contents($header), $model);
        }


        // Header
        $footer = $fileResolver->resolveFile("Config/email-templates/footer.html");
        if ($footer) {
            $model["footer"] = $templateParser->parseTemplateText(file_get_contents($footer), $model);
        }

        parent::__construct($templateName, $model, $recipients, $from, null, null, null, $replyTo, $attachments);
    }

}
