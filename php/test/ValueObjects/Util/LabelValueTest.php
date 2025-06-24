<?php

namespace Kiniauth\Test\ValueObjects\Util;

use Kiniauth\Objects\Security\UserSummary;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\Util\LabelValue;

include_once "autoloader.php";

class LabelValueTest extends TestBase {

    public function testCanGenerateLabelValueObjectsFromObjectArray() {
        $sourceArray = [
            new UserSummary("Biggles", UserSummary::STATUS_ACTIVE),
            new UserSummary("Boggles", UserSummary::STATUS_PENDING),
            new UserSummary("Buggles", UserSummary::STATUS_SUSPENDED)
        ];

        $expected = [
            new LabelValue("Biggles", UserSummary::STATUS_ACTIVE),
            new LabelValue("Boggles", UserSummary::STATUS_PENDING),
            new LabelValue("Buggles", UserSummary::STATUS_SUSPENDED)
        ];

        $this->assertEquals($expected, LabelValue::generateFromObjectArray($sourceArray, "name", "status"));


    }

}