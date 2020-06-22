<?php

namespace Kiniauth\Services\Security\RouteInterceptor;

use Kiniauth\Exception\Security\MissingCSRFHeaderException;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\MVC\Request\Headers;
use Kinikit\MVC\Request\Request;

include_once __DIR__ . "/../../../autoloader.php";

class AccountRouteInterceptorTest extends TestBase {


    private $authenticationService;

    /**
     * @var AccountRouteInterceptor
     */
    private $accountRouteInterceptor;

    /**
     * @var SecurityService
     */
    private $securityService;


    public function setUp(): void {
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $this->securityService = Container::instance()->get(SecurityService::class);
        $this->accountRouteInterceptor = new AccountRouteInterceptor($this->securityService);

        $_SERVER["HTTP_HOST"] = "localhost";
    }


    public function testAccountControllerURLsAreNotAccessibleByPublicAndRequireCSRFTokenHeader() {

        $_SERVER["REQUEST_URI"] = "/account/somecontroller?mynameistest";

        // Guest
        $this->authenticationService->logout();

        try {
            $this->accountRouteInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (MissingCSRFHeaderException $e) {
            // Success
        }

        // Account user
        $this->authenticationService->login("simon@peterjonescarwash.com", "password");

        try {
            $this->accountRouteInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (MissingCSRFHeaderException $e) {
            // Success
        }


        // Root user
        $this->authenticationService->login("admin@kinicart.com", "password");
        $_SERVER["HTTP_X_CSRF_TOKEN"] = $this->securityService->getCSRFToken();
        $this->accountRouteInterceptor->beforeRoute(new Request(new Headers()));


        // API login - shouldn't have access to account web context
        $this->authenticationService->apiAuthenticate("TESTAPIKEY", "TESTAPISECRET");
        $_SERVER["HTTP_X_CSRF_TOKEN"] = $this->securityService->getCSRFToken();
        try {
            $this->accountRouteInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        $this->assertTrue(true);

    }


}
