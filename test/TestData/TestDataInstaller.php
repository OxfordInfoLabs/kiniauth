<?php


namespace Kiniauth\Test\TestData;


use DirectoryIterator;
use Kiniauth\DB\DBInstaller;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Init;
use Kinikit\Persistence\ORM\ORM;


class TestDataInstaller {

    /**
     * @var ObjectBinder
     */
    private $objectBinder;

    /**
     * @var ORM
     */
    private $orm;

    public function __construct() {
        $this->objectBinder = Container::instance()->get(ObjectBinder::class);
        $this->orm = Container::instance()->get(ORM::class);
    }


    /**
     * Run the db installer.  If core only is supplied, only the core kinicart schema
     * will be installed otherwise any custom application schema will also be run from the
     * local DB directory.
     */
    public function run($coreOnly = false, $installDB = true, $sourceDirectory = "../src", $testDirectory = ".") {

        if ($installDB) {
            $dbInstaller = new DBInstaller();
            $dbInstaller->run($coreOnly, $sourceDirectory);
        }

        // Initialise the application.
        Container::instance()->get(\Kiniauth\Services\Application\BootstrapService::class);

        /**
         * @var $interceptor \Kiniauth\Services\Security\ActiveRecordInterceptor
         */
        $interceptor = Container::instance()->get(\Kiniauth\Services\Security\ActiveRecordInterceptor::class);


        $directories = array(array(__DIR__ . "/..", "Kiniauth"));

        if (!$coreOnly) {
            $directories[] = array($testDirectory, Configuration::readParameter("application.namespace"));
        }

        $interceptor->executeInsecure(function () use ($directories) {

            foreach ($directories as list($directory, $namespace)) {
                if (file_exists($directory . "/TestData"))
                    $this->processTestDataDirectory($directory . "/TestData", $namespace);
            }

        });

    }


    // Install test data
    public static function runFromComposer($event) {

        new Init();

        $sourceDirectory = $event && isset($event->getComposer()->getPackage()->getConfig()["source-directory"]) ?
            $event->getComposer()->getPackage()->getConfig()["source-directory"] : ".";


        $testDirectory = $event && isset($event->getComposer()->getPackage()->getConfig()["test-directory"]) ?
            $event->getComposer()->getPackage()->getConfig()["test-directory"] : ".";

        $testDirectory = getcwd() . "/" . $testDirectory;

        chdir($sourceDirectory);

        $testDataInstaller = new TestDataInstaller();
        $testDataInstaller->run(false, true, ".", $testDirectory);

    }


    // Process test data directory looking for objects.
    private function processTestDataDirectory($directory, $baseNamespace) {

        $iterator = new DirectoryIterator($directory);
        $filepaths = array();
        foreach ($iterator as $item) {

            if ($item->isDot())
                continue;

            if ($item->isDir()) {
                $this->processTestDataDirectory($item->getRealPath(), $baseNamespace);
                continue;
            }

            if ($item->getExtension() != "json")
                continue;

            $filepaths[] = $item->getRealPath();


        }

        sort($filepaths);

        foreach ($filepaths as $filepath) {

            // Now grab the filename and explode on TestData
            $exploded = explode("TestData/", $filepath);
            $targetClass = explode(".", $exploded[1]);

            if (is_numeric(strpos($targetClass[0], "Kiniauth"))) {
                $targetClass = str_replace(array("/", "Kiniauth"), array("\\", "Kiniauth\\Objects"), $targetClass[0]);
            } else {
                $targetClass = $baseNamespace . "\\Objects\\" . str_replace("/", "\\", $targetClass[0]);
            }

            $items = json_decode(file_get_contents($filepath), true);

            $objects = $this->objectBinder->bindFromArray($items, $targetClass . "[]", false);

            // Save the objects.
            $this->orm->save($objects);

        }


    }

}


