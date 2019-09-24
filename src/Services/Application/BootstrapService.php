<?php


namespace Kiniauth\Services\Application;

use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\ObjectInterceptor;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\WebServices\Security\DefaultControllerAccessInterceptor;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\Interceptor\ORMInterceptorProcessor;

/**
 * Generic bootstrap class - should be called early in application flow to ensure that global data is set up correctly.
 */
class BootstrapService {

    private $authenticationService;
    private $securityService;
    private $fileResolver;
    private $ormInterceptorProcessor;
    private $activeRecordInterceptor;


    /**
     * Construct with authentication service
     *
     * @param \Kiniauth\Services\Security\AuthenticationService $authenticationService
     * @param \Kiniauth\Services\Security\ActiveRecordInterceptor $activeRecordInterceptor
     * @param \Kiniauth\Services\Security\SecurityService $securityService
     * @param ORMInterceptorProcessor $ormInterceptorProcessor
     * @param FileResolver $fileResolver
     *
     */
    public function __construct($authenticationService, $activeRecordInterceptor, $securityService, $ormInterceptorProcessor, $fileResolver) {

        $this->authenticationService = $authenticationService;
        $this->activeRecordInterceptor = $activeRecordInterceptor;
        $this->securityService = $securityService;
        $this->ormInterceptorProcessor = $ormInterceptorProcessor;
        $this->fileResolver = $fileResolver;
        $this->run();

    }


    /**
     * Run the bootstrapping logic.
     */
    private function run() {

        // Ensure kinicart is appended as a source base and an application namespace.
        $this->fileResolver->addSearchPath(__DIR__ . "/../..");

        $this->ormInterceptorProcessor->addInterceptor("*", get_class($this->activeRecordInterceptor));

        // Add the generic object method interceptor
        Container::instance()->addInterceptor(new ObjectInterceptor($this->activeRecordInterceptor, $this->securityService));

        // Add the controller method interceptor
        Container::instance()->addInterceptor(new DefaultControllerAccessInterceptor($this->securityService, $this->authenticationService));

        // Update the active parent account using the HTTP Referer.
        $this->authenticationService->updateActiveParentAccount(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "");

    }

}
