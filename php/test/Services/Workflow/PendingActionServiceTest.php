<?php

namespace Kiniauth\Test\Services\Workflow;

use Kiniauth\Objects\Workflow\PendingAction;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

class PendingActionServiceTest extends TestBase {

    /**
     * @var PendingActionService
     */
    private $pendingActionService;

    /**
     * @var DatabaseConnection
     */
    private $databaseConnection;


    public function setUp(): void {
        $this->pendingActionService = Container::instance()->get(PendingActionService::class);
        $this->databaseConnection = Container::instance()->get(DatabaseConnection::class);


    }


    public function testCanCreateNewPendingAction() {

        $identifier = $this->pendingActionService->createPendingAction("EXAMPLE", 1, "Wonderful World", "P3D");

        $action = PendingAction::filter("WHERE identifier = ?", $identifier)[0];
        $this->assertEquals("EXAMPLE", $action->getType());
        $this->assertEquals(1, $action->getObjectId());
        $this->assertEquals("Wonderful World", $action->getData());
        $this->assertEquals((new \DateTime())->add(new \DateInterval("P3D"))->format("d/m/Y"),
            $action->getExpiryDateTime()->format("d/m/Y"));
        $this->assertEquals($identifier, $action->getIdentifier());


        $identifier = $this->pendingActionService->createPendingAction("EXAMPLE", 1, "Wonderful World", null, date_create_from_format("d/m/Y H:i:s", "01/01/2025 10:10:10"), "product");

        $action = PendingAction::filter("WHERE identifier = ?", $identifier)[0];
        $this->assertEquals("EXAMPLE", $action->getType());
        $this->assertEquals(1, $action->getObjectId());
        $this->assertEquals("Wonderful World", $action->getData());
        $this->assertEquals(date_create_from_format("d/m/Y H:i:s", "01/01/2025 10:10:10"), $action->getExpiryDateTime());
        $this->assertEquals($identifier, $action->getIdentifier());
        $this->assertEquals("product", $action->getObjectType());


    }

    public function testCanGetPendingActionsByIdentifier() {

        $identifier1 = $this->pendingActionService->createPendingAction("EXAMPLE", 1, "My World", "P3D");
        $identifier2 = $this->pendingActionService->createPendingAction("EXAMPLE", 1, "Wonderful World", "P3D");

        $this->assertEquals(PendingAction::filter("WHERE identifier = ?", $identifier1)[0], $this->pendingActionService->getPendingActionByIdentifier("EXAMPLE", $identifier1));
        $this->assertEquals(PendingAction::filter("WHERE identifier = ?", $identifier2)[0], $this->pendingActionService->getPendingActionByIdentifier("EXAMPLE", $identifier2));


        try {
            $this->pendingActionService->getPendingActionByIdentifier("EXAMPLE", "BADIDENT");
            $this->fail("Should have thrown here");
        } catch (ItemNotFoundException $e) {
            // Success
        }

    }


    public function testCanGetAllActionsForTypeAndObjectIdAndOptionalType() {

        $identifier1 = $this->pendingActionService->createPendingAction("NEW", 3);
        $identifier2 = $this->pendingActionService->createPendingAction("NEW", 3);
        $identifier3 = $this->pendingActionService->createPendingAction("NEW", 3);
        $identifier4 = $this->pendingActionService->createPendingAction("NEW", 4, null, null, null, "text");
        $identifier5 = $this->pendingActionService->createPendingAction("NEW", 4, null, null, null);


        $allActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("NEW", 3);

        $this->assertEquals(3, sizeof($allActions));
        $this->assertEquals(PendingAction::filter("WHERE identifier = ?", $identifier3)[0], $allActions[0]);
        $this->assertEquals(PendingAction::filter("WHERE identifier = ?", $identifier2)[0], $allActions[1]);
        $this->assertEquals(PendingAction::filter("WHERE identifier = ?", $identifier1)[0], $allActions[2]);

        $allActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("NEW", 4);
        $this->assertEquals(2, sizeof($allActions));
        $this->assertEquals(PendingAction::filter("WHERE identifier = ?", $identifier5)[0], $allActions[0]);
        $this->assertEquals(PendingAction::filter("WHERE identifier = ?", $identifier4)[0], $allActions[1]);

        $allActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("NEW", 4, "text");
        $this->assertEquals(1, sizeof($allActions));
        $this->assertEquals(PendingAction::filter("WHERE identifier = ?", $identifier4)[0], $allActions[0]);

    }

    public function testExpiredActionsAreRemovedAndNotReturnedOnGet() {

        $pendingAction = new PendingAction("Test Expired", 33, null, "P5D");
        $pendingAction->save();

        $this->databaseConnection->execute("UPDATE ka_pending_action SET expiry_date_time = '2019-01-01 00:00:00' WHERE id = " . $pendingAction->getId());

        try {
            PendingAction::fetch($pendingAction->getId());
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            // Success
        }

        $this->assertEquals(0, $this->databaseConnection->query("SELECT COUNT(*) total FROM ka_pending_action WHERE id = " . $pendingAction->getId())->fetchAll()[0]["total"]);


    }
}
