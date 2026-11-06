<?php

namespace Controllers\Communication\Messaging;

use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;


include_once "autoloader.php";

class Messaging extends TestBase {

    /**
     * @var Messaging
     */
    private $messagingController;

    public function setUp(): void {
        $this->messagingController = Container::instance()->get(Messaging::class);
    }

    public function testCanGetAllMessagesFromAThread() {

        $this->assertTrue(true);

    }

}