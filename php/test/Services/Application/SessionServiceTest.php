<?php

namespace Kiniauth\Test\Services\Application;

use Kiniauth\Services\Application\SessionService;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;

include_once __DIR__ . "/../../autoloader.php";

/**
 * Class SessionServiceTest
 */
class SessionServiceTest extends TestBase {

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @var SessionService
     */
    private $sessionService;


    /**
     * @var SecurityService
     */
    private $securityService;


    public function setUp(): void {
        parent::setUp();
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $this->sessionService = Container::instance()->get(SessionService::class);
        $this->securityService = Container::instance()->get(SecurityService::class);
    }


    public function testCanGetSessionData() {

        // Super Admin
        AuthenticationHelper::login("admin@kinicart.com", "password");
        $sessionData = $this->sessionService->getSessionData();
        $loggedIn = $this->securityService->getLoggedInSecurableAndAccount();
        $this->assertEquals($loggedIn[0], $sessionData->getUser());
        $this->assertNull($sessionData->getAccount());


        // Account admin
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $sessionData = $this->sessionService->getSessionData();
        $loggedIn = $this->securityService->getLoggedInSecurableAndAccount();
        $this->assertEquals($loggedIn[0], $sessionData->getUser());
        $this->assertEquals($loggedIn[1]->generateSummary(), $sessionData->getAccount());

        // Api one
        $this->authenticationService->apiAuthenticate("GLOBALACCOUNTAPIKEY", "GLOBALACCOUNTAPISECRET");

        $sessionData = $this->sessionService->getSessionData();
        $loggedIn = $this->securityService->getLoggedInSecurableAndAccount();
        $this->assertNull($sessionData->getUser());
        $this->assertEquals($loggedIn[0], $sessionData->getApiKey());
        $this->assertEquals($loggedIn[1]->generateSummary(), $sessionData->getAccount());


    }

}
