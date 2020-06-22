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

class AdminRouteInterceptorTest extends TestBase {


    private $authenticationService;

    /**
     * @var AdminRouteInterceptor
     */
    private $adminRouteInterceptor;

    /**
     * @var SecurityService
     */
    private $securityService;


    public function setUp(): void {
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $this->securityService = Container::instance()->get(SecurityService::class);
        $this->adminRouteInterceptor = new AdminRouteInterceptor($this->securityService);

        $_SERVER["HTTP_HOST"] = "localhost";
    }


    public function testAdminControllerURLsAreNotAccessibleByNonSuperusers() {

        $_SERVER["REQUEST_URI"] = "/admin/somecontroller?mynameistest";

        // Guest
        $this->authenticationService->logout();


        try {
            $this->adminRouteInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (MissingCSRFHeaderException $e) {
            // Success
        }


        // Account user
        $this->authenticationService->login("simon@peterjonescarwash.com", "password");
        $_SERVER["HTTP_X_CSRF_TOKEN"] = $this->securityService->getCSRFToken();

        try {
            $this->adminRouteInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }


        // Root user
        $this->authenticationService->login("admin@kinicart.com", "password");
        $_SERVER["HTTP_X_CSRF_TOKEN"] = $this->securityService->getCSRFToken();




        // API login
        $this->authenticationService->apiAuthenticate("TESTAPIKEY", "TESTAPISECRET");
        $_SERVER["HTTP_X_CSRF_TOKEN"] = $this->securityService->getCSRFToken();

        try {
            $this->adminRouteInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }


        $this->assertTrue(true);

    }




}
