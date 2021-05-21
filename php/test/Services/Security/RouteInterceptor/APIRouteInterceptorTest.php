<?php


namespace Kiniauth\Services\Security\RouteInterceptor;


use Kiniauth\Exception\Security\InvalidAPICredentialsException;
use Kiniauth\Exception\Security\MissingAPICredentialsException;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\MVC\Request\Headers;
use Kinikit\MVC\Request\Request;

class APIRouteInterceptorTest extends TestBase {

    private $authenticationService;

    /**
     * @var APIRouteInterceptor
     */
    private $apiRouteInterceptor;

    /**
     * @var SecurityService
     */
    private $securityService;


    public function setUp(): void {
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $this->securityService = Container::instance()->get(SecurityService::class);
        $this->apiRouteInterceptor = new APIRouteInterceptor($this->securityService, $this->authenticationService);

        $_SERVER["HTTP_HOST"] = "localhost";
    }


    public function testAPIControllerURLsAreOnlyAccessibleByLoggedInAccountAndRequireAPIKeyAndSecretForEachRequest() {

        $_SERVER["REQUEST_URI"] = "/api/somecontroller?mynameistest";

        // Guest
        $this->authenticationService->logout();

        try {
            $this->apiRouteInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (MissingAPICredentialsException $e) {
            // Success
        }

        // Account user
        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");
        try {
            $this->apiRouteInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (MissingAPICredentialsException $e) {
            // Success
        }


        // Root user
        AuthenticationHelper::login("admin@kinicart.com", "password");
        try {
            $this->apiRouteInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (MissingAPICredentialsException $e) {
            // Success
        }


        // API login first - this should still fail.
        $this->authenticationService->apiAuthenticate("TESTAPIKEY", "TESTAPISECRET");

        try {
            $this->apiRouteInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("should have thrown here");
        } catch (MissingAPICredentialsException $e) {
            // Success
        }


        // Now tweak the URL to include bad credentials
        $this->authenticationService->logout();

        $_SERVER["REQUEST_URI"] = "/api/somecontroller?mynameistest?apiKey=BADKEY&apiSecret=BADSECRET";
        $_GET = array("apiKey" => "BADKEY", "apiSecret" => "BADSECRET");

        try {
            $this->apiRouteInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("should have thrown here");
        } catch (InvalidAPICredentialsException $e) {
            // Success
        }

        $_SERVER["REQUEST_URI"] = "/api/somecontroller?mynameistest";
        $_GET = [];
        $_SERVER["HTTP_API_KEY"] = "BADKEY";
        $_SERVER["HTTP_API_SECRET"] = "BADSECRET";

        // Bad credentials supplied as header
        try {
            $this->apiRouteInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("should have thrown here");
        } catch (InvalidAPICredentialsException $e) {
            // Success
        }


        // Finally good credentials as GET params
        $_SERVER["REQUEST_URI"] = "/api/somecontroller?mynameistest?apiKey=TESTAPIKEY&apiSecret=TESTAPISECRET";
        $_GET = array("apiKey" => "TESTAPIKEY", "apiSecret" => "TESTAPISECRET");

        $this->apiRouteInterceptor->beforeRoute(new Request(new Headers()));

        // Good creds as header
        $_SERVER["REQUEST_URI"] = "/api/somecontroller?mynameistest";
        $_GET = [];
        $_SERVER["HTTP_API_KEY"] = "TESTAPIKEY";
        $_SERVER["HTTP_API_SECRET"] = "TESTAPISECRET";
        
        $this->apiRouteInterceptor->beforeRoute(new Request(new Headers()));

        $this->assertTrue(true);

    }


}
