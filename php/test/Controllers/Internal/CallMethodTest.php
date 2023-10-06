<?php

namespace Kiniauth\Test\Controllers\Internal;

use Kiniauth\Controllers\Internal\CallMethod;
use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Objects\Security\UserSummary;
use Kiniauth\Services\Application\Session;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\Util\Asynchronous\HttpLoopbackRequest;
use Kiniauth\ValueObjects\Util\Asynchronous\HttpLoopbackResponse;
use Kinikit\Core\DependencyInjection\Container;


include_once "autoloader.php";

class CallMethodTest extends TestBase {

    /**
     * @var CallMethod
     */
    private $callMethod;


    public function setUp(): void {
        $this->callMethod = Container::instance()->get(CallMethod::class);
    }

    public function testResponseReturnedCorrectlyForSuccessfulMethodCalledCorrectlyForHttpRequest() {

        $httpLoopbackRequest = new HttpLoopbackRequest(TestCallObject::class, "firstMethod", [
            "int" => 3, "string" => "Hello", "float" => 3.56, "boolean" => true, "intArray" => [1, 2, 3, 4, 5]
        ], ["int" => "integer", "string" => "string", "float" => "float", "boolean" => "boolean",
            "intArray" => "int[]"], "string");


        // Call the method
        $response = $this->callMethod->callMethod($httpLoopbackRequest);

        $this->assertEquals(new HttpLoopbackResponse(HttpLoopbackResponse::STATUS_SUCCESS, "Success", "string"), $response);

    }


    public function testResponseReturnedCorrectlyWhenExceptionRaisedForMethodCallForHttpRequest() {

        $httpLoopbackRequest = new HttpLoopbackRequest(TestCallObject::class, "secondMethod", [
            "user" => ["name" => "Mark"], "userArray" => [["name" => "John"], ["name" => "Pete"]],
        ], ["user" => UserSummary::class, "userArray" => UserSummary::class . "[]"], "string");


        // Call the method
        $response = $this->callMethod->callMethod($httpLoopbackRequest);

        $this->assertEquals(new HttpLoopbackResponse(HttpLoopbackResponse::STATUS_EXCEPTION, new \Exception("Hello world of fun and adventure"), \Exception::class), $response);

    }


    public function testBecomesUserForPassedIdIfRequestPassedWithUserId() {

        /**
         * @var Session $session
         */
        $session = Container::instance()->get(Session::class);

        // Check not logged in as user 3
        $this->assertNull($session->__getLoggedInSecurable());


        $httpLoopbackRequest = new HttpLoopbackRequest(TestCallObject::class, "firstMethod", [
            "int" => 3, "string" => "Hello", "float" => 3.56, "boolean" => true, "intArray" => [1, 2, 3, 4, 5]
        ], ["int" => "integer", "string" => "string", "float" => "float", "boolean" => "boolean",
            "intArray" => "int[]"], "string", 3, "USER", 2);


        // Call the method
        $response = $this->callMethod->callMethod($httpLoopbackRequest);

        $this->assertEquals(new HttpLoopbackResponse(HttpLoopbackResponse::STATUS_SUCCESS, "Success", "string"), $response);


        // Check logged in as user 3
        $this->assertEquals(3, $session->__getLoggedInSecurable()->getId());


    }


    public function testBecomesAPIKeyForPassedAPIKeyIfRequestPassedWithApiKeyId() {

        /**
         * @var Session $session
         */
        $session = Container::instance()->get(Session::class);

        $session->clearAll();

        // Check not logged in as user 3
        $this->assertNull($session->__getLoggedInSecurable());


        $httpLoopbackRequest = new HttpLoopbackRequest(TestCallObject::class, "firstMethod", [
            "int" => 3, "string" => "Hello", "float" => 3.56, "boolean" => true, "intArray" => [1, 2, 3, 4, 5]
        ], ["int" => "integer", "string" => "string", "float" => "float", "boolean" => "boolean",
            "intArray" => "int[]"], "string", 1, "API_KEY", 2);


        // Call the method
        $response = $this->callMethod->callMethod($httpLoopbackRequest);

        $this->assertEquals(new HttpLoopbackResponse(HttpLoopbackResponse::STATUS_SUCCESS, "Success", "string"), $response);


        // Check logged in as api key 1
        $this->assertEquals(1, $session->__getLoggedInSecurable()->getId());
        $this->assertInstanceOf(APIKey::class, $session->__getLoggedInSecurable());


    }


    public function testBecomesAccountIfRequestPassedWithoutSecurableIdButWithAccountId() {

        /**
         * @var Session $session
         */
        $session = Container::instance()->get(Session::class);

        $session->clearAll();

        // Check not logged in as user 3
        $this->assertNull($session->__getLoggedInSecurable());


        $httpLoopbackRequest = new HttpLoopbackRequest(TestCallObject::class, "firstMethod", [
            "int" => 3, "string" => "Hello", "float" => 3.56, "boolean" => true, "intArray" => [1, 2, 3, 4, 5]
        ], ["int" => "integer", "string" => "string", "float" => "float", "boolean" => "boolean",
            "intArray" => "int[]"], "string", null, null, 3);


        // Call the method
        $response = $this->callMethod->callMethod($httpLoopbackRequest);

        $this->assertEquals(new HttpLoopbackResponse(HttpLoopbackResponse::STATUS_SUCCESS, "Success", "string"), $response);


        // Check logged in as api key 1
        $this->assertNull($session->__getLoggedInSecurable());
        $this->assertEquals(3, $session->__getLoggedInAccount()->getAccountId());


    }

}