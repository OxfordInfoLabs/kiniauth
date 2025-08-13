<?php

namespace Kiniauth\Test\Services\Webhook;

use Kiniauth\Objects\Webhook\Webhook;
use Kiniauth\Objects\Webhook\WebhookSummary;
use Kiniauth\Services\Security\KeyPairService;
use Kiniauth\Services\Webhook\WebhookService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Feed\PushFeed;
use Kinintel\Objects\Feed\PushFeedSummary;
use PHPUnit\Framework\MockObject\MockObject;

include_once "autoloader.php";

class WebhookServiceTest extends TestBase {


    /**
     * @var KeyPairService|MockObject
     */
    private KeyPairService|MockObject $keyPairService;

    /**
     * @var HttpRequestDispatcher|MockObject
     */
    private HttpRequestDispatcher|MockObject $requestDispatcher;

    /**
     * @var WebhookService
     */
    private WebhookService $webhookService;


    public function setUp(): void {
        $this->keyPairService = MockObjectProvider::mock(KeyPairService::class);
        $this->requestDispatcher = MockObjectProvider::mock(HttpRequestDispatcher::class);
        $this->webhookService = new WebhookService($this->requestDispatcher, $this->keyPairService);
    }

    public function testCanCreateReadFilterAndRemoveWebHooks() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $webhook1 = new WebhookSummary("Example Push 1", "/test", Request::METHOD_PUT, "application/json",
            ["x-other" => "test"]);

        $id1 = $this->webhookService->saveWebhook($webhook1, "bongo", 1);
        $this->assertNotNull($id1);

        $webhook2 = new WebhookSummary("Example Push 2", "/source", Request::METHOD_PUT, "application/xml",
            []);

        $id2 = $this->webhookService->saveWebhook($webhook2, null, 1);
        $this->assertNotNull($id2);

        $webhook3 = new WebhookSummary("Example Push 3", "/batch", Request::METHOD_PATCH, "application/xml",
            []);

        $id3 = $this->webhookService->saveWebhook($webhook3, null, 2);
        $this->assertNotNull($id3);


        // Check some filtered results
        $this->assertEquals([WebhookSummary::fetch(1)], $this->webhookService->filterWebhooks("", "bongo", 0, 10, 1));
        $this->assertEquals([WebhookSummary::fetch(1), WebhookSummary::fetch(2)], $this->webhookService->filterWebhooks("", null, 0, 10, 1));
        $this->assertEquals([WebhookSummary::fetch(3)], $this->webhookService->filterWebhooks("", null, 0, 10, 2));


        $this->webhookService->removeWebhook(1);

        try {
            Webhook::fetch(1);
            $this->fail("Should have deleted");
        } catch (ObjectNotFoundException $e) {
        }


    }


    public function testWebhookProcessedCorrectlyForSimpleWebhookWithValidEndpoint() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $webhook = new WebhookSummary("Example Push 1", "/test", Request::METHOD_PUT, "application/json",
            ["x-other" => "test"]);

        $id = $this->webhookService->saveWebhook($webhook, "bongo", 1);
        $this->assertNotNull($id);

        $this->webhookService->processWebhook($id, "PINK PANTHER RIDES HOME AGAIN");



    }


}