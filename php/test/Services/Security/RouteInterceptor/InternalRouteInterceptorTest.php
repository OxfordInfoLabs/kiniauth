<?php

namespace Kiniauth\Test\Services\Security\RouteInterceptor;

use Kiniauth\Services\Security\RouteInterceptor\InternalRouteInterceptor;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Security\Hash\HashProvider;
use Kinikit\Core\Security\Hash\SHA512HashProvider;
use Kinikit\MVC\Request\Headers;
use Kinikit\MVC\Request\Request;
use Kinikit\MVC\Routing\RouteNotFoundException;

include_once "autoloader.php";

class InternalRouteInterceptorTest extends TestBase {

    /**
     * @var InternalRouteInterceptor
     */
    private $routeInterceptor;

    public function setUp(): void {
        $this->routeInterceptor = Container::instance()->get(InternalRouteInterceptor::class);
    }


    /**
     * @doesNotPerformAssertions
     */
    public function testAccessDeniedExceptionRaisedOnBeforeRouteIfMissingOrBadAuthHashSuppliedForInternalRequest() {

        try {
            $this->routeInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (RouteNotFoundException $e) {
        }

        $_SERVER["HTTP_AUTH_HASH"] = "BADHASH";
        try {
            $this->routeInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (RouteNotFoundException $e) {
        }

    }


    /**
     * @doesNotPerformAssertions
     */
    public function testBeforeRouteSucceedsIfCorrectHashValuePassed() {

        /**
         * @var HashProvider $hashProvider
         */
        $hashProvider = Container::instance()->get(SHA512HashProvider::class);
        $_SERVER["HTTP_AUTH_HASH"] = $hashProvider->generateHash("ABCDEFGHIJKLM");
        $this->routeInterceptor->beforeRoute(new Request(new Headers()));

    }

}