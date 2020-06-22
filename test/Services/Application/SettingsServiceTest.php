<?php


namespace Kiniauth\Test\Services\Application;


use Kiniauth\Services\Application\SettingsService;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\MVC\Request\URL;

include_once __DIR__ . "/../../autoloader.php";

class SettingsServiceTest extends TestBase {

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @var SettingsService
     */
    private $settingsService;


    public function setUp(): void {
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $this->settingsService = Container::instance()->get(SettingsService::class);
    }

    public function testCanGetAllParentAccountSettingValuesForLoggedInUser() {

        $this->authenticationService->updateActiveParentAccount(new URL("https://samdavis.org"));

        $this->authenticationService->login("james@smartcoasting.org", "password");

        $settings = $this->settingsService->getParentAccountSettingValues();

        $this->assertTrue(isset($settings["logo"]));
        $this->assertEquals("Sam Davis Retail Outlet", $settings["brandName"]);
        $this->assertEquals(["samdavis.org"], $settings["referringDomains"]);


        $this->authenticationService->updateActiveParentAccount(new URL("https://kinicart.example"));

        $this->authenticationService->login("james@smartcoasting.org", "password");

        $settings = $this->settingsService->getParentAccountSettingValues();


        $this->assertTrue(isset($settings["logo"]));
        $this->assertEquals("Kiniauth Example", $settings["brandName"]);
        $this->assertEquals(["kinicart.example", "kinicart.test"], $settings["referringDomains"]);

    }


    public function testCanGetAllParentAccountSettingValuesForExplicitUserOrAccount() {

        $this->authenticationService->logout();

        $settings = $this->settingsService->getParentAccountSettingValues(5);
        $this->assertTrue(isset($settings["logo"]));
        $this->assertEquals("Sam Davis Retail Outlet", $settings["brandName"]);
        $this->assertEquals(["samdavis.org"], $settings["referringDomains"]);

        $settings = $this->settingsService->getParentAccountSettingValues(2);
        $this->assertTrue(isset($settings["logo"]));
        $this->assertEquals("Kiniauth Example", $settings["brandName"]);
        $this->assertEquals(["kinicart.example", "kinicart.test"], $settings["referringDomains"]);

        $settings = $this->settingsService->getParentAccountSettingValues(null, 9);
        $this->assertTrue(isset($settings["logo"]));
        $this->assertEquals("Sam Davis Retail Outlet", $settings["brandName"]);
        $this->assertEquals(["samdavis.org"], $settings["referringDomains"]);

        $settings = $this->settingsService->getParentAccountSettingValues(null, 2);
        $this->assertTrue(isset($settings["logo"]));
        $this->assertEquals("Kiniauth Example", $settings["brandName"]);
        $this->assertEquals(["kinicart.example", "kinicart.test"], $settings["referringDomains"]);


    }

}
