<?php

namespace Kiniauth\Test;

use Kiniauth\Test\TestData\TestDataInstaller;


class TestBase extends \PHPUnit\Framework\TestCase {

    private static $run = false;

    public static function setUpBeforeClass():void {
        if (!self::$run) {
            $testDataInstaller = new TestDataInstaller();
            $testDataInstaller->run(true);
            self::$run = true;
        }
    }

}
