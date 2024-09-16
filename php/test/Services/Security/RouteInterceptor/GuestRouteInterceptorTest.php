<?php

namespace Kiniauth\Test\Services\Security\RouteInterceptor;

use Kiniauth\Exception\Security\MissingCSRFHeaderException;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\RouteInterceptor\GuestRouteInterceptor;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\MVC\Request\Headers;
use Kinikit\MVC\Request\Request;

include_once __DIR__ . "/../../../autoloader.php";

class GuestRouteInterceptorTest extends TestBase {

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @var SecurityService
     */
    private $securityService;

    /**
     * @var GuestRouteInterceptor
     */
    private $guestRouteInterceptor;


    public function setUp(): void{
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $this->securityService = Container::instance()->get(SecurityService::class);
        $this->guestRouteInterceptor = new GuestRouteInterceptor($this->securityService, $this->authenticationService);

        $_SERVER["HTTP_HOST"] = "localhost";
        $_SERVER["HTTP_REFERER"] = "https://kinicart.test/hello";

    }

    /**
     * @doesNotPerformAssertions
     */
    public function testGeneralGuestControllerURLsAreAccessibleByPublicButRequireCSRFTokenHeader() {

        // Guest
        $this->authenticationService->logout();


        $_SERVER["REQUEST_URI"] = "/guest/somecontroller?mynameistest";

        try {
            $this->guestRouteInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (MissingCSRFHeaderException $e) {
            // Success
        }

        $_SERVER["HTTP_X_CSRF_TOKEN"] = $this->securityService->getCSRFToken();
        $this->guestRouteInterceptor->beforeRoute(new Request(new Headers()));

    }

    /**
     * @doesNotPerformAssertions
     */
    public function testWhitelistedPublicRoutesDoNotRequireCSRFToken(){

        // Guest
        $this->authenticationService->logout();


        $_SERVER["REQUEST_URI"] = "/guest/session";
        $this->guestRouteInterceptor->beforeRoute(new Request(new Headers()));

        $_SERVER["REQUEST_URI"] = "/guest/session?spurious=true";
        $this->guestRouteInterceptor->beforeRoute(new Request(new Headers()));


        $_SERVER["REQUEST_URI"] = "/guest/auth/logout";
        $this->guestRouteInterceptor->beforeRoute(new Request(new Headers()));
    }

}
