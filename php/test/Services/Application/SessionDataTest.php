<?php

namespace Kiniauth\Test\Services\Application;

use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Application\SessionData;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;

include_once "autoloader.php";

class SessionDataTest extends TestBase {

    public function testSessionDataGeneratedCorrectlyWhenLoggedInAsUser() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $sessionData = Container::instance()->get(SessionData::class);

        $this->assertNotNull($sessionData->getSecurable());
        $this->assertNotNull($sessionData->getAccount());

    }

    public function testSessionDataGeneratedCorrectlyWhenLoggedInAsAPIKey() {

        AuthenticationHelper::apiAuthenticate("GLOBALACCOUNTAPIKEY", "GLOBALACCOUNTAPISECRET");

        $sessionData = Container::instance()->get(SessionData::class);

        $this->assertNotNull($sessionData->getSecurable());
        $this->assertNotNull($sessionData->getAccount());

    }

}