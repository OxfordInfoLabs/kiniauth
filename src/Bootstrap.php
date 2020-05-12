<?php

namespace Kiniauth;

use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\GlobalRouteInterceptor;
use Kiniauth\Services\Security\ObjectInterceptor;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\Validation\PasswordFieldValidator;
use Kinikit\Core\ApplicationBootstrap;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\Validator;
use Kinikit\MVC\Routing\RouteInterceptorProcessor;
use Kinikit\Persistence\ORM\Interceptor\ORMInterceptorProcessor;

class Bootstrap implements ApplicationBootstrap {

    private $authenticationService;
    private $securityService;
    private $ormInterceptorProcessor;
    private $activeRecordInterceptor;
    private $routeInterceptorProcessor;
    private $validator;

    /**
     * Construct with authentication service
     *
     * @param AuthenticationService $authenticationService
     * @param ActiveRecordInterceptor $activeRecordInterceptor
     * @param SecurityService $securityService
     * @param ORMInterceptorProcessor $ormInterceptorProcessor
     * @param RouteInterceptorProcessor $routeInterceptorProcessor
     * @param Validator $validator
     *
     */
    public function __construct($authenticationService, $activeRecordInterceptor, $securityService, $ormInterceptorProcessor, $routeInterceptorProcessor,
                                $validator) {

        $this->authenticationService = $authenticationService;
        $this->activeRecordInterceptor = $activeRecordInterceptor;
        $this->securityService = $securityService;
        $this->ormInterceptorProcessor = $ormInterceptorProcessor;
        $this->routeInterceptorProcessor = $routeInterceptorProcessor;
        $this->validator = $validator;

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

        if (!Configuration::readParameter("default.decorator")) {
            Configuration::instance()->addParameter("default.decorator", "DefaultDecorator");
        }

        $this->validator->addValidator(new PasswordFieldValidator("password", "The password must be at least 8 characters with one capital, one lowercase and one number"));

    }


}
