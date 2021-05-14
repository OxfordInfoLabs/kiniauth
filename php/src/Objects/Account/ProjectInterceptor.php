<?php


namespace Kiniauth\Objects\Account;


use Kinikit\Core\Util\StringUtils;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;


/**
 * Intercept save requests for projects
 *
 */
class ProjectInterceptor extends DefaultORMInterceptor {

    /**
     * @var DatabaseConnection
     */
    private $databaseConnection;

    /**
     * ContactInterceptor constructor.
     *
     * @param DatabaseConnection $databaseConnection
     */
    public function __construct($databaseConnection) {
        $this->databaseConnection = $databaseConnection;
    }


    /**
     * Ensure we have a project number
     *
     * @param Project $object
     */
    public function preSave($object) {

        if (!$object->getKey() && $object->getAccountId()) {

            $compressedKey = StringUtils::convertToCamelCase($object->getName());
            $proposedKey = $compressedKey;
            $index = 1;
            do {
                $response = $this->databaseConnection->query("SELECT COUNT(*) existing FROM ka_project WHERE account_id = ? AND key = ?", $object->getAccountId(), $proposedKey);
                $existing = $response->fetchAll()[0]["existing"];

                if ($existing == 0)
                    break;

                $index++;
                $proposedKey = $compressedKey . $index;

            } while (true);

            $object->setKey($proposedKey);

        }
    }


}