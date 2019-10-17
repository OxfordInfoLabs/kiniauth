<?php

namespace Kiniauth\Test\WebServices\Security;


use Kiniauth\Exception\Security\InvalidAPICredentialsException;
use Kiniauth\Exception\Security\MissingAPICredentialsException;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Test\TestBase;
use Kiniauth\WebServices\Security\GlobalRouteInterceptor;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\MVC\Request\Request;

include_once __DIR__ . "/../../autoloader.php";

class GlobalRouteInterceptorTest extends TestBase {

    private $authenticationService;

    /**
     * @var GlobalRouteInterceptor
     */
    private $globalRouteInterceptor;


    public function setUp():void {
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $securityService = Container::instance()->get(SecurityService::class);
        $this->globalRouteInterceptor = new GlobalRouteInterceptor($securityService, $this->authenticationService);

        $_SERVER["HTTP_HOST"] = "localhost";
    }

    public function testPublicControllerURLsAreAccessibleByAll() {

        $_SERVER["REQUEST_URI"] = "/public/somecontroller?mynameistest";

        // Guest
        $this->authenticationService->logout();
        $this->globalRouteInterceptor->beforeRoute(new Request([]));

        // Account user
        $this->authenticationService->login("simon@peterjonescarwash.com", "password");
        $this->globalRouteInterceptor->beforeRoute(new Request([]));

        // Root user
        $this->authenticationService->login("admin@kinicart.com", "password");
        $this->globalRouteInterceptor->beforeRoute(new Request([]));


        // API login
        $this->authenticationService->apiAuthenticate("TESTAPIKEY", "TESTAPISECRET");
        $this->globalRouteInterceptor->beforeRoute(new Request([]));

        $this->assertTrue(true);

    }


    public function testCustomerControllerURLsAreNotAccessibleByPublic() {

        $_SERVER["REQUEST_URI"] = "/account/somecontroller?mynameistest";

        // Guest
        $this->authenticationService->logout();

        try {
            $this->globalRouteInterceptor->beforeRoute(new Request([]));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        // Account user
        $this->authenticationService->login("simon@peterjonescarwash.com", "password");
        $this->globalRouteInterceptor->beforeRoute(new Request([]));

        // Root user
        $this->authenticationService->login("admin@kinicart.com", "password");
        $this->globalRouteInterceptor->beforeRoute(new Request([]));


        // API login
        $this->authenticationService->apiAuthenticate("TESTAPIKEY", "TESTAPISECRET");
        $this->globalRouteInterceptor->beforeRoute(new Request([]));

        $this->assertTrue(true);

    }


    public function testAdminControllerURLsAreNotAccessibleByNonSuperusers() {

        $_SERVER["REQUEST_URI"] = "/admin/somecontroller?mynameistest";

        // Guest
        $this->authenticationService->logout();

        try {
            $this->globalRouteInterceptor->beforeRoute(new Request([]));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        // Account user
        $this->authenticationService->login("simon@peterjonescarwash.com", "password");
        try {
            $this->globalRouteInterceptor->beforeRoute(new Request([]));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }


        // Root user
        $this->authenticationService->login("admin@kinicart.com", "password");
        $this->globalRouteInterceptor->beforeRoute(new Request([]));


        // API login
        $this->authenticationService->apiAuthenticate("TESTAPIKEY", "TESTAPISECRET");
        try {
            $this->globalRouteInterceptor->beforeRoute(new Request([]));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }


        $this->assertTrue(true);

    }


    public function testAPIControllerURLsAreOnlyAccessibleByLoggedInAccountAndRequireAPIKeyAndSecretForEachRequest() {

        $_SERVER["REQUEST_URI"] = "/api/somecontroller?mynameistest";

        // Guest
        $this->authenticationService->logout();

        try {
            $this->globalRouteInterceptor->beforeRoute(new Request([]));
            $this->fail("Should have thrown here");
        } catch (MissingAPICredentialsException $e) {
            // Success
        }

        // Account user
        $this->authenticationService->login("simon@peterjonescarwash.com", "password");
        try {
            $this->globalRouteInterceptor->beforeRoute(new Request([]));
            $this->fail("Should have thrown here");
        } catch (MissingAPICredentialsException $e) {
            // Success
        }


        // Root user
        $this->authenticationService->login("admin@kinicart.com", "password");
        try {
            $this->globalRouteInterceptor->beforeRoute(new Request([]));
            $this->fail("Should have thrown here");
        } catch (MissingAPICredentialsException $e) {
            // Success
        }


        // API login first - this should still fail.
        $this->authenticationService->apiAuthenticate("TESTAPIKEY", "TESTAPISECRET");

        try {
            $this->globalRouteInterceptor->beforeRoute(new Request([]));
            $this->fail("should have thrown here");
        } catch (MissingAPICredentialsException $e) {
            // Success
        }


        // Now tweak the URL to include bad credentials
        $this->authenticationService->logout();

        $_SERVER["REQUEST_URI"] = "/api/somecontroller?mynameistest?apiKey=BADKEY&apiSecret=BADSECRET";
        $_GET = array("apiKey" => "BADKEY", "apiSecret" => "BADSECRET");

        try {
            $this->globalRouteInterceptor->beforeRoute(new Request([]));
            $this->fail("should have thrown here");
        } catch (InvalidAPICredentialsException $e) {
            // Success
        }


        // Finally good credentials
        $_SERVER["REQUEST_URI"] = "/api/somecontroller?mynameistest?apiKey=TESTAPIKEY&apiSecret=TESTAPISECRET";
        $_GET = array("apiKey" => "TESTAPIKEY", "apiSecret" => "TESTAPISECRET");

        $this->globalRouteInterceptor->beforeRoute(new Request([]));


        $this->assertTrue(true);

    }


}
