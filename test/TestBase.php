<?php

namespace Kiniauth\Test;


use Kiniauth\Bootstrap;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Bootstrapper;
use Kinikit\Persistence\Tools\TestDataInstaller;

class TestBase extends \PHPUnit\Framework\TestCase {

    private static $run = false;

    public static function setUpBeforeClass(): void {

        $bootstrap = Container::instance()->get(Bootstrap::class);
        $bootstrap->setup();


        if (!self::$run) {

            $activeRecordInterceptor = Container::instance()->get(ActiveRecordInterceptor::class);

            $activeRecordInterceptor->executeInsecure(function () {
                $testDataInstaller = Container::instance()->get(TestDataInstaller::class);
                $testDataInstaller->run(true);
            });

            Container::instance()->get(Bootstrapper::class);

            self::$run = true;
        }
    }

}
