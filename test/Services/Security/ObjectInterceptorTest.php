<?php


namespace Kiniauth\Test\Services\Application;


use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Test\Services\Security\TestMethodService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Reflection\ClassInspector;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use Kinikit\MVC\Request\Request;
use Kinikit\MVC\Request\URL;

include_once __DIR__ . "/../../autoloader.php";

class ObjectInterceptorTest extends TestBase {


    /**
     * @var TestMethodService
     */
    private $testMethodService;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;


    /**
     * @var Request
     */
    private $request;

    /**
     * @var ClassInspector
     */
    private $requestInspector;

    /**
     * @var Session
     */
    private $session;


    public function setUp(): void {
        parent::setUp();
        $this->testMethodService = Container::instance()->get(TestMethodService::class);
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $this->request = Container::instance()->get(Request::class);

        /**
         * @var $classInspectorProvider ClassInspectorProvider
         */
        $classInspectorProvider = Container::instance()->get(ClassInspectorProvider::class);
        $this->requestInspector = $classInspectorProvider->getClassInspector(Request::class);

        $this->session = Container::instance()->get(Session::class);

        $this->authenticationService->updateActiveParentAccount(new URL("https://kinicart.test/hello"));

    }


    public function testObjectInterceptorIsDisabledIfAttributeAddedToMethod() {

        $this->authenticationService->logout();

        try {
            $this->testMethodService->normalMethod();
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // As expected
        }


        $this->testMethodService->objectInterceptorDisabledMethod();
        $this->assertTrue(true);

    }


    // Check that access is denied for an exception raised for a method with has privileges.
    public function testAccessDeniedExceptionRaisedForMethodWithHasPrivilegesDefined() {

        $this->authenticationService->logout();

        try {
            $this->testMethodService->accountPermissionRestricted();
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        // Now try logging in as a user without the delete data privilege
        $this->authenticationService->login("regularuser@smartcoasting.org", "password");

        try {
            $this->testMethodService->accountPermissionRestricted();
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        // Now try a user with delete data privilege
        $this->authenticationService->login("mary@shoppingonline.com", "password");
        $this->assertEquals("OK", $this->testMethodService->accountPermissionRestricted());

        // Now try logging in as an administrator
        $this->authenticationService->login("james@smartcoasting.org", "password");
        $this->assertEquals("OK", $this->testMethodService->accountPermissionRestricted());


        $this->authenticationService->logout();

        try {
            $this->testMethodService->otherAccountPermissionRestricted(1, "marko");
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        // Now try logging in as an administrator
        $this->authenticationService->login("james@smartcoasting.org", "password");
        $this->assertEquals("DONE", $this->testMethodService->otherAccountPermissionRestricted(2, "Heydude"));
        $this->assertEquals("DONE", $this->testMethodService->otherAccountPermissionRestricted(3, "Heydude"));

        try {
            $this->testMethodService->otherAccountPermissionRestricted(4, "marko");
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

    }


    public function testCanInjectLoggedInAccountIdAsDefaultValueViaConstant() {

        $this->authenticationService->logout();
        $this->assertEquals(array("Mark", null), $this->testMethodService->loggedInAccountInjection("Mark"));

        // Now try logging in as a user without the delete data privilege
        $this->authenticationService->login("regularuser@smartcoasting.org", "password");
        $this->assertEquals(array("Mark", 1), $this->testMethodService->loggedInAccountInjection("Mark"));

    }


    public function testCanInjectLoggedInUserIdAsDefaultValueViaConstant() {

        $this->authenticationService->logout();
        $this->assertEquals(array("Mark", null), $this->testMethodService->loggedInUserInjection("Mark"));

        // Now try logging in as a user without the delete data privilege
        $this->authenticationService->login("regularuser@smartcoasting.org", "password");
        $this->assertEquals(array("Mark", 10), $this->testMethodService->loggedInUserInjection("Mark"));

    }


    public function testForMethodsWithCaptchaEveryTimeWeExpectACaptchaRequestParameter() {

        try {
            $this->testMethodService->captchaEveryTime();
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }


        $this->requestInspector->setPropertyData($this->request,
            ["captcha" => "54646436543"], "parameters", false);


        // This should succeed
        $this->testMethodService->captchaEveryTime();

        $this->assertTrue(true);
    }


    public function testForMethodsWithCaptchaDelayParameterParameterOnlyRequiredAfterSpecifiedAttempts() {

        // Remove parameters
        $this->requestInspector->setPropertyData($this->request,
            [], "parameters", false);

        $this->requestInspector->setPropertyData($this->request,
            new URL("https://myone.test/guest/service/bing"), "url", false);


        $this->assertEquals(0, $this->session->__getDelayedCaptcha("guest/service/bing"));

        echo "Attempting first failure";

        try {
            $this->testMethodService->captchaAfter1Failure(true);
        } catch (\InvalidArgumentException $e) {
            // Success
        }

        $this->assertEquals(1, $this->session->__getDelayedCaptcha("guest/service/bing"));

        try {
            $this->testMethodService->captchaAfter1Failure();
        } catch (AccessDeniedException $e) {
            // Success
        }

        $this->assertEquals(2, $this->session->__getDelayedCaptcha("guest/service/bing"));


        $this->requestInspector->setPropertyData($this->request,
            ["captcha" => "54646436543"], "parameters", false);


        // This should succeed
        $this->testMethodService->captchaAfter1Failure();


        $this->assertEquals(0, $this->session->__getDelayedCaptcha("guest/service/bing"));

    }


}
