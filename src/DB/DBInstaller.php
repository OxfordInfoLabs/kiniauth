<?php


namespace Kiniauth\DB;


use DirectoryIterator;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\ORM\SchemaGenerator\SchemaGenerator;

class DBInstaller {

    /**
     * Run the db installer.  If core only is supplied, only the core kinicart schema
     * will be installed otherwise any custom application schema will also be installed from the
     * application source directory which defaults to the current directory.
     */
    public function run($coreOnly = false, $sourceDirectory = ".") {

        $databaseConnection = Container::instance()->get(DatabaseConnection::class);
        $schemaGenerator = Container::instance()->get(SchemaGenerator::class);


        // Execute the create schema for both the core and application
        $schemaGenerator->createSchema(__DIR__ . "/../Objects", "Kiniauth\Objects");
        $schemaGenerator->createSchema($sourceDirectory . "/Objects");

        $directories = array(__DIR__ . "/..");
        if (!$coreOnly) $directories[] = $sourceDirectory;

        // Run core (and application) DB installs
        foreach ($directories as $directory) {
            if (file_exists($directory . "/DB"))
                $directoryIterator = new DirectoryIterator($directory . "/DB");
            foreach ($directoryIterator as $item) {
                if ($item->isDot()) continue;
                if ($item->getExtension() != "sql") continue;
                $databaseConnection->executeScript(file_get_contents($item->getRealPath()));
            }
        }
    }


    /**
     * Main clean function.
     */
    public static function runFromComposer($event) {

        $sourceDirectory = $event && isset($event->getComposer()->getPackage()->getConfig()["source-directory"]) ?
            $event->getComposer()->getPackage()->getConfig()["source-directory"] : ".";

        chdir($sourceDirectory);

        $installer = new DBInstaller();
        $installer->run();


    }

}
