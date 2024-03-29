<?php

namespace Kiniauth;

use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\Captcha\CaptchaProvider;
use Kiniauth\Services\Security\GlobalRouteInterceptor;
use Kiniauth\Services\Security\ObjectInterceptor;
use Kiniauth\Services\Security\RouteInterceptor\AccountRouteInterceptor;
use Kiniauth\Services\Security\RouteInterceptor\AdminRouteInterceptor;
use Kiniauth\Services\Security\RouteInterceptor\APIRouteInterceptor;
use Kiniauth\Services\Security\RouteInterceptor\GuestRouteInterceptor;
use Kiniauth\Services\Security\RouteInterceptor\InternalRouteInterceptor;
use Kiniauth\Services\Security\RouteInterceptor\TestRouteInterceptor;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Util\Asynchronous\AMPAsyncAsynchronousProcessor;
use Kiniauth\Services\Util\Asynchronous\AMPParallelAsynchronousProcessor;
use Kiniauth\Services\Workflow\Validation\PasswordFieldValidator;
use Kinikit\Core\ApplicationBootstrap;
use Kinikit\Core\Asynchronous\Processor\AsynchronousProcessor;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\Validator;
use Kinikit\MVC\Request\Request;
use Kinikit\MVC\Routing\RouteInterceptorProcessor;
use Kinikit\Persistence\ORM\Interceptor\ORMInterceptorProcessor;
use Kinikit\Persistence\ORM\ORM;

class Bootstrap implements ApplicationBootstrap {

    private $authenticationService;
    private $securityService;
    private $ormInterceptorProcessor;
    private $activeRecordInterceptor;
    private $routeInterceptorProcessor;
    private $validator;
    private $captchaProvider;
    private $request;
    private $session;
    private $orm;

    /**
     * Construct with authentication service
     *
     * @param AuthenticationService $authenticationService
     * @param ActiveRecordInterceptor $activeRecordInterceptor
     * @param SecurityService $securityService
     * @param ORMInterceptorProcessor $ormInterceptorProcessor
     * @param RouteInterceptorProcessor $routeInterceptorProcessor
     * @param Validator $validator
     * @param CaptchaProvider $captchaProvider
     * @param Request $request
     * @param Session $session
     * @param ORM $orm
     *
     */
    public function __construct($authenticationService, $activeRecordInterceptor, $securityService, $ormInterceptorProcessor, $routeInterceptorProcessor,
                                $validator, $captchaProvider, $request, $session, $orm) {

        $this->authenticationService = $authenticationService;
        $this->activeRecordInterceptor = $activeRecordInterceptor;
        $this->securityService = $securityService;
        $this->ormInterceptorProcessor = $ormInterceptorProcessor;
        $this->routeInterceptorProcessor = $routeInterceptorProcessor;
        $this->validator = $validator;
        $this->captchaProvider = $captchaProvider;
        $this->request = $request;
        $this->session = $session;
        $this->orm = $orm;
    }


    /**
     * Run the bootstrapping logic.
     */
    public function setup() {

        Container::instance()->addInterfaceImplementation(AsynchronousProcessor::class, "ampparallel", AMPParallelAsynchronousProcessor::class);
        Container::instance()->addInterfaceImplementation(AsynchronousProcessor::class, "ampasync", AMPAsyncAsynchronousProcessor::class);

        $this->ormInterceptorProcessor->addInterceptor("*", get_class($this->activeRecordInterceptor));

        // Add the generic object method interceptor
        Container::instance()->addInterceptor(new ObjectInterceptor($this->activeRecordInterceptor, $this->securityService, $this->captchaProvider, $this->request, $this->session, $this->orm));

        // Add the built in route interceptors
        $this->routeInterceptorProcessor->addInterceptor("guest/*", GuestRouteInterceptor::class);
        $this->routeInterceptorProcessor->addInterceptor("account/*", AccountRouteInterceptor::class);
        $this->routeInterceptorProcessor->addInterceptor("admin/*", AdminRouteInterceptor::class);
        $this->routeInterceptorProcessor->addInterceptor("api/*", APIRouteInterceptor::class);
        $this->routeInterceptorProcessor->addInterceptor("test/*", TestRouteInterceptor::class);
        $this->routeInterceptorProcessor->addInterceptor("internal/*", InternalRouteInterceptor::class);


        if (!Configuration::readParameter("default.decorator")) {
            Configuration::instance()->addParameter("default.decorator", "DefaultDecorator");
        }

        $this->validator->addValidator(new PasswordFieldValidator("password", "The password must be at least 8 characters with one capital, one lowercase and one number"));

    }


}
