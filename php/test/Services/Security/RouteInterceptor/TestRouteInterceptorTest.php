<?php

namespace Kiniauth\Services\Security\RouteInterceptor;

use Kiniauth\Test\TestBase;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\MVC\Request\Headers;
use Kinikit\MVC\Request\Request;
use Kinikit\MVC\Routing\RouteNotFoundException;

include_once __DIR__ . "/../../../autoloader.php";

class TestRouteInterceptorTest extends TestBase {


    /**
     * @doesNotPerformAssertions
     */
    public function testAccessDeniedExceptionRaisedForAllCallsToTestRoutesIfTestEnabledFlagNotSet() {

        // Check we get access denied by default
        $routeInterceptor = new TestRouteInterceptor();

        try {
            $routeInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (RouteNotFoundException $e) {
            // Success
        }


        Configuration::instance()->addParameter("test.routes.enabled", true);

        // Should be fine now
        $routeInterceptor->beforeRoute(new Request(new Headers()));


    }


}