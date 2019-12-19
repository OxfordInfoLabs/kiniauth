<?php

namespace Kiniauth\Test\Objects\Communications\Email;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Email\AccountTemplatedEmail;
use Kiniauth\Objects\Communication\Email\SuperUserTemplatedEmail;
use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Application\SettingsService;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Template\TemplateParser;

/**
 * Test cases for the branded templated email
 *
 * Class BrandedTemplatedEmailTest
 */
class BrandedTemplatedEmailTest extends TestBase {

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @var SettingsService
     */
    private $settingsService;


    /**
     * @var TemplateParser
     */
    private $templateParser;

    public function setUp(): void {
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $this->settingsService = Container::instance()->get(SettingsService::class);
        $this->templateParser = Container::instance()->get(TemplateParser::class);
    }


    public function testAccountTemplatedEmailsMergeParentBrandSettingsAndAccountIntoModel() {

        $this->authenticationService->login("admin@kinicart.com", "password");

        $accountTemplatedEmail = new AccountTemplatedEmail(2, "test", ["title" => "Mr", "name" => "Bob"]);

        $model = $accountTemplatedEmail->getModel();

        // Check our custom model is still intact.
        $this->assertEquals("Mr", $model["title"]);
        $this->assertEquals("Bob", $model["name"]);

        // Check the additional models are there which we expect
        $this->assertEquals($this->settingsService->getParentAccountSettingValues(2), $model["settings"]);
        $this->assertEquals(Account::fetch(2), $model["account"]);

        // Also check for header and footer convenience models
        $this->assertEquals($this->templateParser->parseTemplateText(file_get_contents(__DIR__ . "/../../../../src/Config/email-templates/header.html"), $model), $model["header"]);
        $this->assertEquals($this->templateParser->parseTemplateText(file_get_contents(__DIR__ . "/../../../../src/Config/email-templates/footer.html"), $model), $model["footer"]);

        $this->assertEquals("Kiniauth Example <info@kiniauth.example>", $accountTemplatedEmail->getFrom());
        $this->assertEquals("noreply@kiniauth.example", $accountTemplatedEmail->getReplyTo());
        $this->assertEquals(["Simon Car Wash <simon@peterjonescarwash.com>", "James Smartcoasting <james@smartcoasting.org>", "mary@shoppingonline.com"], $accountTemplatedEmail->getRecipients());


        $accountTemplatedEmail = new AccountTemplatedEmail(5, "test", ["title" => "Mr", "name" => "Bob"]);

        $model = $accountTemplatedEmail->getModel();

        // Check our custom model is still intact.
        $this->assertEquals("Mr", $model["title"]);
        $this->assertEquals("Bob", $model["name"]);

        // Check the additional models are there which we expect
        $this->assertEquals($this->settingsService->getParentAccountSettingValues(5), $model["settings"]);
        $this->assertEquals(Account::fetch(5), $model["account"]);

        // Also check for header and footer convenience models
        $this->assertEquals($this->templateParser->parseTemplateText(file_get_contents(__DIR__ . "/../../../../src/Config/email-templates/header.html"), $model), $model["header"]);
        $this->assertEquals($this->templateParser->parseTemplateText(file_get_contents(__DIR__ . "/../../../../src/Config/email-templates/footer.html"), $model), $model["footer"]);

        $this->assertEquals("Sam Davis Retail <info@samdavis.org>", $accountTemplatedEmail->getFrom());
        $this->assertEquals("noreply@samdavis.org", $accountTemplatedEmail->getReplyTo());
        $this->assertEquals(["James Smart Coasting <james@smartcoasting.org>"], $accountTemplatedEmail->getRecipients());


    }


    public function testUserTemplatedEmailsMergeParentBrandSettingsAndUserIntoModel() {

        $this->authenticationService->login("admin@kinicart.com", "password");

        $userTemplatedEmail = new UserTemplatedEmail(2, "test", ["title" => "Mrs", "name" => "Jane"]);

        $model = $userTemplatedEmail->getModel();

        // Check our custom model is still intact.
        $this->assertEquals("Mrs", $model["title"]);
        $this->assertEquals("Jane", $model["name"]);

        // Check the additional models are there which we expect
        $this->assertEquals($this->settingsService->getParentAccountSettingValues(null, 2), $model["settings"]);
        $this->assertEquals(User::fetch(2), $model["user"]);

        // Also check for header and footer convenience models
        $this->assertEquals($this->templateParser->parseTemplateText(file_get_contents(__DIR__ . "/../../../../src/Config/email-templates/header.html"), $model), $model["header"]);
        $this->assertEquals($this->templateParser->parseTemplateText(file_get_contents(__DIR__ . "/../../../../src/Config/email-templates/footer.html"), $model), $model["footer"]);

        $this->assertEquals("Kiniauth Example <info@kiniauth.example>", $userTemplatedEmail->getFrom());
        $this->assertEquals("noreply@kiniauth.example", $userTemplatedEmail->getReplyTo());
        $this->assertEquals(["Sam Davis <sam@samdavisdesign.co.uk>"], $userTemplatedEmail->getRecipients());


        $userTemplatedEmail = new UserTemplatedEmail(9, "test", ["title" => "Mrs", "name" => "Jane"]);

        $model = $userTemplatedEmail->getModel();

        // Check our custom model is still intact.
        $this->assertEquals("Mrs", $model["title"]);
        $this->assertEquals("Jane", $model["name"]);

        // Check the additional models are there which we expect
        $this->assertEquals($this->settingsService->getParentAccountSettingValues(null, 9), $model["settings"]);
        $this->assertEquals(User::fetch(9), $model["user"]);

        // Also check for header and footer convenience models
        $this->assertEquals($this->templateParser->parseTemplateText(file_get_contents(__DIR__ . "/../../../../src/Config/email-templates/header.html"), $model), $model["header"]);
        $this->assertEquals($this->templateParser->parseTemplateText(file_get_contents(__DIR__ . "/../../../../src/Config/email-templates/footer.html"), $model), $model["footer"]);

        $this->assertEquals("Sam Davis Retail <info@samdavis.org>", $userTemplatedEmail->getFrom());
        $this->assertEquals("noreply@samdavis.org", $userTemplatedEmail->getReplyTo());
        $this->assertEquals(["James Smart Coasting <james@smartcoasting.org>"], $userTemplatedEmail->getRecipients());


    }


    public function testSuperUserTemplatedEmailsMergeTopLevelBrandSettingsIntoModel() {

        $superUserEmail = new SuperUserTemplatedEmail("test", ["title" => "Mrs", "name" => "Jane"]);

        $model = $superUserEmail->getModel();

        // Check our custom model is still intact.
        $this->assertEquals("Mrs", $model["title"]);
        $this->assertEquals("Jane", $model["name"]);

        // Check the additional models are there which we expect
        $this->assertEquals($this->settingsService->getParentAccountSettingValues(null, null), $model["settings"]);

        // Also check for header and footer convenience models
        $this->assertEquals($this->templateParser->parseTemplateText(file_get_contents(__DIR__ . "/../../../../src/Config/email-templates/header.html"), $model), $model["header"]);
        $this->assertEquals($this->templateParser->parseTemplateText(file_get_contents(__DIR__ . "/../../../../src/Config/email-templates/footer.html"), $model), $model["footer"]);

        $this->assertEquals("Kiniauth Example <info@kiniauth.example>", $superUserEmail->getFrom());
        $this->assertEquals("noreply@kiniauth.example", $superUserEmail->getReplyTo());
        $this->assertEquals(["Administrator <admin@kinicart.com>"], $superUserEmail->getRecipients());
        
    }


}
