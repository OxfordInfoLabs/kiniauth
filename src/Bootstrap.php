<?php

namespace Kiniauth;

use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\ObjectInterceptor;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\WebServices\Security\GlobalRouteInterceptor;
use Kinikit\Core\ApplicationBootstrap;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\MVC\Routing\RouteInterceptorProcessor;
use Kinikit\Persistence\ORM\Interceptor\ORMInterceptorProcessor;

class Bootstrap implements ApplicationBootstrap {

    private $authenticationService;
    private $securityService;
    private $ormInterceptorProcessor;
    private $activeRecordInterceptor;
    private $routeInterceptorProcessor;

    /**
     * Construct with authentication service
     *
     * @param AuthenticationService $authenticationService
     * @param ActiveRecordInterceptor $activeRecordInterceptor
     * @param SecurityService $securityService
     * @param ORMInterceptorProcessor $ormInterceptorProcessor
     * @param RouteInterceptorProcessor $routeInterceptorProcessor
     *
     */
    public function __construct($authenticationService, $activeRecordInterceptor, $securityService, $ormInterceptorProcessor, $routeInterceptorProcessor) {

        $this->authenticationService = $authenticationService;
        $this->activeRecordInterceptor = $activeRecordInterceptor;
        $this->securityService = $securityService;
        $this->ormInterceptorProcessor = $ormInterceptorProcessor;
        $this->routeInterceptorProcessor = $routeInterceptorProcessor;

    }


    /**
     * Run the bootstrapping logic.
     */
    public function setup() {

        $this->ormInterceptorProcessor->addInterceptor("*", get_class($this->activeRecordInterceptor));

        // Add the generic object method interceptor
        Container::instance()->addInterceptor(new ObjectInterceptor($this->activeRecordInterceptor, $this->securityService));

        // Add the global route interceptor
        $this->routeInterceptorProcessor->addInterceptor("*", GlobalRouteInterceptor::class);

        // Update the active parent account using the HTTP Referer.
        $this->authenticationService->updateActiveParentAccount(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "");

    }


}
