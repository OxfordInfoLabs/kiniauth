<?php

namespace Kiniauth\Test\Services\Util\Asynchronous;

use Kiniauth\Services\Util\Asynchronous\AMPParallelAsynchronousProcessor;
use Kiniauth\Services\Util\Asynchronous\AMPParallelTask;
use Kiniauth\Services\Util\Asynchronous\TestAMPAsynchronous;
use Kiniauth\Test\TestBase;
use Kinikit\Core\Asynchronous\Asynchronous;
use Kinikit\Core\Asynchronous\AsynchronousClassMethod;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\HTTP\Dispatcher\AMPRequestDispatcher;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\HTTP\Response\Response;
use Kinikit\MVC\API\ValueObjects\HTTPMethod;

include_once __DIR__ . "/../../../autoloader.php";

class AMPParallelAsynchronousProcessorTest extends TestBase {

    /** @var AMPParallelAsynchronousProcessor */
    private $processor;

    public function setUp(): void {
        $this->processor = Container::instance()->get(AMPParallelAsynchronousProcessor::class);
    }

    public function testExecuteAndWaitCorrectlyWaitsForAllBackgroundThreadsToExecuteAndUpdatesDataAndStatus() {

        $asynchronous1 = new TestAMPAsynchronous("Mary");
        $asynchronous2 = new TestAMPAsynchronous("Mark");
        $asynchronous3 = new TestAMPAsynchronous("James");

        // Execute and wait
        $this->processor->executeAndWait([$asynchronous1, $asynchronous2, $asynchronous3]);

        // Check that the status and data is all updated
        $this->assertEquals("Mary", $asynchronous1->getName());
        $this->assertEquals("Evaluated: Mary", $asynchronous1->getEvaluatedProperty());
        $this->assertEquals("Returned: Mary", $asynchronous1->getReturnValue());
        $this->assertEquals(Asynchronous::STATUS_COMPLETED, $asynchronous1->getStatus());

        $this->assertEquals("Mark", $asynchronous2->getName());
        $this->assertEquals("Evaluated: Mark", $asynchronous2->getEvaluatedProperty());
        $this->assertEquals("Returned: Mark", $asynchronous2->getReturnValue());
        $this->assertEquals(Asynchronous::STATUS_COMPLETED, $asynchronous2->getStatus());

        $this->assertEquals("James", $asynchronous3->getName());
        $this->assertEquals("Evaluated: James", $asynchronous3->getEvaluatedProperty());
        $this->assertEquals("Returned: James", $asynchronous3->getReturnValue());
        $this->assertEquals(Asynchronous::STATUS_COMPLETED, $asynchronous3->getStatus());

    }

    public function testExecuteAndWaitCorrectlyWaitsForAllBackgroundThreadsToExecuteAndCapturesExceptionsOnFailure() {

        $asynchronous1 = new TestAMPAsynchronous("Mark");
        $asynchronous2 = new TestAMPAsynchronous("FAIL");
        $asynchronous3 = new TestAMPAsynchronous("James");

        // Execute and wait
        $this->processor->executeAndWait([$asynchronous1, $asynchronous2, $asynchronous3]);

        // Check that the status and data is all updated
        $this->assertEquals("Mark", $asynchronous1->getName());
        $this->assertEquals("Evaluated: Mark", $asynchronous1->getEvaluatedProperty());
        $this->assertEquals("Returned: Mark", $asynchronous1->getReturnValue());
        $this->assertEquals(Asynchronous::STATUS_COMPLETED, $asynchronous1->getStatus());

        $this->assertEquals("FAIL", $asynchronous2->getName());
        $this->assertNull($asynchronous2->getEvaluatedProperty());
        $this->assertNull($asynchronous2->getReturnValue());
        $this->assertEquals("Failed", $asynchronous2->getExceptionData()["message"]);
        $this->assertEquals(Asynchronous::STATUS_FAILED, $asynchronous2->getStatus());

        $this->assertEquals("James", $asynchronous3->getName());
        $this->assertEquals("Evaluated: James", $asynchronous3->getEvaluatedProperty());
        $this->assertEquals("Returned: James", $asynchronous3->getReturnValue());
        $this->assertEquals(Asynchronous::STATUS_COMPLETED, $asynchronous3->getStatus());

    }

    public function testDoHttpRequestsInParallel() {
        $urls = ["https://dnsrf.org", "https://dnsrf.org/about/index.html"];
        $asyncInstances = array_map(function($url) {
            $request = new Request($url, "GET");
            $asyncFunction = new AsynchronousClassMethod(AMPRequestDispatcher::class, "dispatch", ["request" => $request]);
            return $asyncFunction;
        }, $urls);
        $results = $this->processor->executeAndWait($asyncInstances);

        /** @var Response $responseDNSRF */
        $responseDNSRF = $results[0]->getReturnValue();

        /** @var Response $responseAbout */
        $responseAbout = $results[1]->getReturnValue();

        $this->assertTrue(str_contains($responseDNSRF->getBody(), "dnsrf"));
        $this->assertTrue(str_contains($responseAbout->getBody(), "dnsrf"));
    }

}