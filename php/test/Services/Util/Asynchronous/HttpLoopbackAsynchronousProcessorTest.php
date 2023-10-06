<?php

namespace Kiniauth\Test\Services\Util\Asynchronous;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserSummary;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Util\Asynchronous\HttpLoopbackAsynchronousProcessor;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\Util\Asynchronous\HttpLoopbackRequest;
use Kiniauth\ValueObjects\Util\Asynchronous\HttpLoopbackResponse;
use Kinikit\Core\Asynchronous\Asynchronous;
use Kinikit\Core\Asynchronous\AsynchronousClassMethod;
use Kinikit\Core\Asynchronous\AsynchronousFunction;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\WrongParametersException;
use Kinikit\Core\HTTP\Dispatcher\HttpMultiRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\HTTP\Response\Response;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use Kinikit\Core\Security\Hash\HashProvider;
use Kinikit\Core\Security\Hash\SHA512HashProvider;
use Kinikit\Core\Serialisation\JSON\ObjectToJSONConverter;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;

include_once "autoloader.php";

class HttpLoopbackAsynchronousProcessorTest extends TestBase {

    /**
     * @var HttpLoopbackAsynchronousProcessor
     */
    private $processor;


    /**
     * @var MockObject
     */
    private $multiRequestDispatcher;


    /**
     * @var HashProvider
     */
    private $hashProvider;

    /**
     * @var MockObject
     */
    private $session;


    public function setUp(): void {
        $this->hashProvider = new SHA512HashProvider();
        $this->session = MockObjectProvider::instance()->getMockInstance(Session::class);
        $this->multiRequestDispatcher = MockObjectProvider::instance()->getMockInstance(HttpMultiRequestDispatcher::class);
        $this->processor = new HttpLoopbackAsynchronousProcessor($this->multiRequestDispatcher, $this->hashProvider, Container::instance()->get(ClassInspectorProvider::class),
            Container::instance()->get(ObjectBinder::class), Container::instance()->get(ObjectToJSONConverter::class), $this->session);
    }


    /**
     * @doesNotPerformAssertions
     */
    public function testExceptionRaisedIfAttemptToExecuteAsynchronousWhichIsNotAClassMethod() {

        $asynchronous1 = new AsynchronousFunction(function () {
            return "Hello";
        });

        $asynchronous2 = new AsynchronousClassMethod(UserService::class, "getUserAccounts", []);

        try {
            $this->processor->executeAndWait([$asynchronous1, $asynchronous2]);
            $this->fail("Should have thrown here");
        } catch (WrongParametersException $e) {
        }
    }


    public function testAsynchronousRequestsAreSentAndUpdatedCorrectlyWithResultsUsingHttpMultiRequestFor() {

        $this->session->returnValue("__getLoggedInSecurable", new User("mark@test.com", "123", "Mark", 0, 1));
        $this->session->returnValue("__getLoggedInAccount", new Account("Mark", 0, Account::STATUS_ACTIVE, 1));


        $objectToJSONConverter = Container::instance()->get(ObjectToJSONConverter::class);

        $authHash = $this->hashProvider->generateHash("ABCDEFGHIJKLM");

        $converter = Container::instance()->get(ObjectToJSONConverter::class);

        $expectedRequest1 = new Request("http://kiniauth.test/internal/callMethod", Request::METHOD_POST, [],
            $converter->convert(new HttpLoopbackRequest(UserService::class, "getUserAccounts", [], ["userId" => "mixed"], "void", 1, "USER", 1)), new Headers([
                "AUTH-HASH" => $authHash
            ]));


        $expectedRequest2 = new Request("http://kiniauth.test/internal/callMethod", Request::METHOD_POST, [],
            $converter->convert(new HttpLoopbackRequest(UserService::class, "getUser", ["id" => 2], ["id" => "mixed"], "\\" . User::class, 1, "USER", 1)), new Headers([
                "AUTH-HASH" => $authHash
            ]));


        $response1 = new Response(new ReadOnlyStringStream($objectToJSONConverter->convert(new HttpLoopbackResponse(HttpLoopbackResponse::STATUS_SUCCESS, [1, 2, 3], "int[]"))), 200, new \Kinikit\Core\HTTP\Response\Headers(), $expectedRequest1);

        $response2 = new Response(new ReadOnlyStringStream($objectToJSONConverter->convert(new HttpLoopbackResponse(HttpLoopbackResponse::STATUS_SUCCESS, new UserSummary("Mark", "ACTIVE"), UserSummary::class))), 200, new \Kinikit\Core\HTTP\Response\Headers(), $expectedRequest2);


        $this->multiRequestDispatcher->returnValue("dispatch", [$response1, $response2], [[$expectedRequest1, $expectedRequest2]]);


        $asynchronous1 = new AsynchronousClassMethod(UserService::class, "getUserAccounts", []);
        $asynchronous2 = new AsynchronousClassMethod(UserService::class, "getUser", ["id" => 2]);

        $this->processor->executeAndWait([$asynchronous1, $asynchronous2]);

        $this->assertEquals(Asynchronous::STATUS_COMPLETED, $asynchronous1->getStatus());
        $this->assertEquals([1, 2, 3], $asynchronous1->getReturnValue());

        $this->assertEquals(Asynchronous::STATUS_COMPLETED, $asynchronous2->getStatus());
        $this->assertEquals(new UserSummary("Mark", "ACTIVE"), $asynchronous2->getReturnValue());


    }


}